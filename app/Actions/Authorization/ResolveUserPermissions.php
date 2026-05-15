<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Enums\Permission as PermissionEnum;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as Cache;

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

        $rolePerms = $user->roles()
            ->where(function ($q) use ($now) {
                $q->whereNull('role_user.expires_at')
                    ->orWhere('role_user.expires_at', '>', $now);
            })
            ->with('permissions:id,slug')
            ->get()
            ->flatMap->permissions
            ->pluck('slug');

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

        return $rolePerms
            ->merge($grants)
            ->unique()
            ->reject(fn ($slug) => in_array($slug, $denies, true))
            ->mapWithKeys(fn ($slug) => [$slug => true])
            ->all();
    }
}
