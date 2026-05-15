<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Actions\Authorization\ResolveUserPermissions;
use App\Enums\Permission;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Access\Response;
use Illuminate\Database\Eloquent\Model;

final readonly class TenantAuthorizer
{
    public function __construct(private ResolveUserPermissions $resolve) {}

    /**
     * Returns Response::allow() / Response::deny('reason') based on a layered check:
     *  1. Super admin bypass (handled at Gate::before, but kept here too for direct callers).
     *  2. User must be active.
     *  3. Tenant must be present and active.
     *  4. Resource (if provided) must belong to the same tenant.
     *  5. User must have the required permission.
     */
    public function allows(User $user, Permission $permission, ?Tenant $tenant = null, ?Model $resource = null): Response
    {
        if ($user->is_super_admin) {
            return Response::allow();
        }

        if (! $user->is_active) {
            return Response::deny('User account is inactive.');
        }

        $tenant ??= $user->tenant;

        if ($tenant === null || ! $tenant->is_active) {
            return Response::deny('Tenant is inactive or missing.');
        }

        if ($resource !== null && isset($resource->tenant_id) && $resource->tenant_id !== $tenant->id) {
            return Response::deny('Cross-tenant access is forbidden.');
        }

        $permissions = $this->resolve->handle($user);

        return isset($permissions[$permission->value])
            ? Response::allow()
            : Response::deny("Missing permission: {$permission->value}");
    }
}
