<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Enums\Permission as PermissionEnum;
use App\Models\ResourcePermission;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Relationship-/instance-based access control (Improvement 2.8).
 *
 * Checks whether a user holds a permission for one *specific* resource instance
 * (e.g. "deals.update on deal #123"), independent of their roles. It is purely
 * additive: it only ever grants and is consulted as a fallback after the static
 * permission map says "no".
 */
final class InstancePermissionGate
{
    public function allows(User $user, PermissionEnum $permission, Model $resource): bool
    {
        return ResourcePermission::query()
            ->where('user_id', $user->id)
            ->where('resource_type', $resource->getMorphClass())
            ->where('resource_id', $resource->getKey())
            ->whereHas('permission', fn ($q) => $q->where('slug', $permission->value))
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->exists();
    }
}
