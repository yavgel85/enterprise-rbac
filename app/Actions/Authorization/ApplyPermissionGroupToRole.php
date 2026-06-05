<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User;
use DomainException;

final readonly class ApplyPermissionGroupToRole
{
    public function __construct(private SyncRolePermissions $sync) {}

    /**
     * Merge the group's permissions into the role's existing set. All the
     * usual guards (system-role protection, "can't grant what you lack")
     * are inherited from SyncRolePermissions.
     */
    public function handle(User $actor, Role $role, PermissionGroup $group): void
    {
        if ($group->tenant_id !== null && $group->tenant_id !== $role->tenant_id) {
            throw new DomainException('This permission group belongs to a different tenant.');
        }

        $current = $role->permissions()->pluck('slug')->all();
        $groupSlugs = $group->permissions()->pluck('slug')->all();

        $merged = array_values(array_unique([...$current, ...$groupSlugs]));

        $this->sync->handle($actor, $role, $merged);
    }
}
