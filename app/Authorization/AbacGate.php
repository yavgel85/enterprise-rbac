<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Enums\Permission as PermissionEnum;
use App\Models\PermissionCondition;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Attribute-Based Access Control gate (Improvement 2.4).
 *
 * Runs *after* a static permission has been granted. It loads any declarative
 * conditions attached to that permission (optionally scoped to a role / tenant)
 * and requires that ALL applicable conditions hold for the resource at hand.
 *
 * It is purely additive: a permission with no conditions behaves exactly as
 * before. Conditions can only further restrict, never grant.
 */
final readonly class AbacGate
{
    private const CACHE_KEY = 'rbac:abac:conditioned_slugs';

    public function __construct(private ConditionEvaluator $evaluator) {}

    public function passes(User $user, PermissionEnum $permission, ?Model $resource, ?Tenant $tenant): bool
    {
        // Fast path: nothing to evaluate without a concrete resource, or when
        // this permission has no conditions defined anywhere.
        if ($resource === null || ! in_array($permission->value, self::conditionedSlugs(), true)) {
            return true;
        }

        $rows = PermissionCondition::query()
            ->whereHas('permission', fn ($q) => $q->where('slug', $permission->value))
            ->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenant?->id))
            ->get();

        if ($rows->isEmpty()) {
            return true;
        }

        $activeRoleIds = $user->activeRoles()->pluck('roles.id')->all();

        $applicable = $rows->filter(
            fn (PermissionCondition $row) => $row->role_id === null || in_array($row->role_id, $activeRoleIds, true)
        );

        if ($applicable->isEmpty()) {
            return true;
        }

        $context = $this->context($user, $resource);

        return $applicable->every(
            fn (PermissionCondition $row) => $this->evaluator->satisfies($context, (array) $row->conditions)
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function context(User $user, Model $resource): array
    {
        $resourceAttributes = $resource->attributesToArray();

        return [
            'user' => $user->attributesToArray(),
            'resource' => $resourceAttributes,
            // Also expose attributes under the model's snake name, e.g. "deal.status".
            Str::snake(class_basename($resource)) => $resourceAttributes,
        ];
    }

    /**
     * Cached set of permission slugs that have at least one condition row.
     *
     * @return list<string>
     */
    public static function conditionedSlugs(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, fn () => PermissionCondition::query()
            ->join('permissions', 'permissions.id', '=', 'permission_conditions.permission_id')
            ->distinct()
            ->pluck('permissions.slug')
            ->all());
    }

    public static function flushCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
