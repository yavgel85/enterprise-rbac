<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Collection;

final readonly class ResolveUserPermissions
{
    public function __construct(private Cache $cache) {}

    /**
     * Returns the resolved permission set as a fast lookup map: ['users.view' => true, ...].
     *
     * @return array<string, true>
     */
    public function handle(User $user): array
    {
        if ($user->is_super_admin) {
            return collect(PermissionEnum::cases())
                ->mapWithKeys(fn (PermissionEnum $p) => [$p->value => true])
                ->all();
        }

        $ttl = (int) config('rbac.cache_ttl', 3600);

        return $this->cache->remember(
            $this->cacheKey($user),
            $ttl,
            fn () => $this->resolve($user)
        );
    }

    public static function cacheKey(User $user): string
    {
        $tenant = $user->tenant_id ?? 'null';

        return "rbac:tenant:{$tenant}:user:{$user->id}:permissions";
    }

    /**
     * @return array<string, true>
     */
    protected function resolve(User $user): array
    {
        $now = now();

        $activeRoleIds = $user->roles()
            ->where(function ($q) use ($now) {
                $q->whereNull('role_user.expires_at')
                    ->orWhere('role_user.expires_at', '>', $now);
            })
            ->pluck('roles.id')
            ->all();

        $rolePerms = $this->rolePermissionSlugs($activeRoleIds);

        $grants = $user->directPermissions()
            ->wherePivot('type', 'grant')
            ->where(function ($q) use ($now) {
                $q->whereNull('permission_user.expires_at')
                    ->orWhere('permission_user.expires_at', '>', $now);
            })
            ->pluck('slug');

        $denies = $user->directPermissions()
            ->wherePivot('type', 'deny')
            ->where(function ($q) use ($now) {
                $q->whereNull('permission_user.expires_at')
                    ->orWhere('permission_user.expires_at', '>', $now);
            })
            ->pluck('slug')
            ->all();

        // Wildcards ("deals.*") are expanded to concrete slugs on both the
        // grant and deny sides so that the cached map stays a simple lookup.
        $granted = PermissionEnum::expandWildcards($rolePerms->merge($grants));
        $denied = PermissionEnum::expandWildcards($denies);

        return collect($granted)
            ->unique()
            ->reject(fn ($slug) => in_array($slug, $denied, true))
            ->mapWithKeys(fn ($slug) => [$slug => true])
            ->all();
    }

    /**
     * Collect permission slugs from the given roles AND every ancestor
     * reachable through parent_id (role inheritance).
     *
     * @param  list<int>  $roleIds
     * @return Collection<int, string>
     */
    protected function rolePermissionSlugs(array $roleIds): Collection
    {
        if ($roleIds === []) {
            return collect();
        }

        $closure = $this->roleClosureIds($roleIds);

        return Permission::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('roles.id', $closure))
            ->pluck('slug');
    }

    /**
     * Expand a set of role ids into the set including all their ancestors,
     * guarded against cycles and bounded by a depth limit.
     *
     * @param  list<int>  $roleIds
     * @return list<int>
     */
    protected function roleClosureIds(array $roleIds, int $maxDepth = 20): array
    {
        $all = array_values(array_unique($roleIds));
        $frontier = $all;
        $depth = 0;

        while ($frontier !== [] && $depth < $maxDepth) {
            $parents = Role::query()
                ->withoutGlobalScopes()
                ->whereIn('id', $frontier)
                ->whereNotNull('parent_id')
                ->pluck('parent_id')
                ->all();

            $new = array_values(array_diff($parents, $all));
            if ($new === []) {
                break;
            }

            $all = array_merge($all, $new);
            $frontier = $new;
            $depth++;
        }

        return $all;
    }
}
