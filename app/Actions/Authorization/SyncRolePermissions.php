<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class SyncRolePermissions
{
    public function __construct(private ForgetUserPermissionsCache $forget) {}

    /**
     * Sync the role's permissions with the provided list of slugs.
     *
     * @param  list<string>  $permissionSlugs
     */
    public function handle(User $actor, Role $role, array $permissionSlugs): void
    {
        if ($role->is_system && ! $actor->is_super_admin) {
            throw new DomainException('Only super-admin can edit permissions on system roles.');
        }

        $validSlugs = array_map(fn (PermissionEnum $p) => $p->value, PermissionEnum::cases());
        $unknown = array_diff($permissionSlugs, $validSlugs);
        if ($unknown !== []) {
            throw new DomainException('Unknown permission slugs: '.implode(', ', $unknown));
        }

        if (! $actor->is_super_admin) {
            $actorPermissions = array_keys($actor->allPermissions());
            $missing = array_diff($permissionSlugs, $actorPermissions);
            if ($missing !== []) {
                throw new DomainException(
                    'You cannot grant permissions you do not hold: '.implode(', ', $missing)
                );
            }
        }

        DB::transaction(function () use ($role, $permissionSlugs) {
            $ids = Permission::query()
                ->whereIn('slug', $permissionSlugs)
                ->pluck('id')
                ->all();

            $role->permissions()->sync($ids);
            $this->forget->forRole($role);
        });
    }
}
