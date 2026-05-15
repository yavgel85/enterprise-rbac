<?php

declare(strict_types=1);

namespace App\Policies;

use App\Authorization\TenantAuthorizer;
use App\Enums\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final readonly class RolePolicy
{
    public function __construct(private TenantAuthorizer $auth) {}

    public function viewAny(User $user): Response
    {
        return $this->auth->allows($user, Permission::RolesView);
    }

    public function view(User $user, Role $role): Response
    {
        return $this->auth->allows($user, Permission::RolesView, resource: $role);
    }

    public function create(User $user): Response
    {
        return $this->auth->allows($user, Permission::RolesCreate);
    }

    public function update(User $user, Role $role): Response
    {
        $check = $this->auth->allows($user, Permission::RolesUpdate, resource: $role);
        if ($check->denied()) {
            return $check;
        }

        if ($role->is_system) {
            return Response::deny('System roles cannot be edited.');
        }

        return Response::allow();
    }

    public function delete(User $user, Role $role): Response
    {
        $check = $this->auth->allows($user, Permission::RolesDelete, resource: $role);
        if ($check->denied()) {
            return $check;
        }

        if ($role->is_system) {
            return Response::deny('System roles cannot be deleted.');
        }

        return Response::allow();
    }

    public function assign(User $user): Response
    {
        return $this->auth->allows($user, Permission::RolesAssign);
    }

    public function syncPermissions(User $user, Role $role): Response
    {
        $check = $this->auth->allows($user, Permission::PermissionsAssign, resource: $role);
        if ($check->denied()) {
            return $check;
        }

        if ($role->is_system) {
            return Response::deny('Permissions on system roles can only be edited by super admins.');
        }

        return Response::allow();
    }
}
