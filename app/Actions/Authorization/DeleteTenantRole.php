<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Models\Role;
use DomainException;

final readonly class DeleteTenantRole
{
    public function __construct(private ForgetUserPermissionsCache $forget) {}

    public function handle(Role $role): void
    {
        if ($role->is_system) {
            throw new DomainException('System roles cannot be deleted.');
        }

        if ($role->users()->exists()) {
            throw new DomainException('Cannot delete a role that is still assigned to users. Reassign them first.');
        }

        $this->forget->forRole($role);
        $role->delete();
    }
}
