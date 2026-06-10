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
    public function __construct(
        private ResolveUserPermissions $resolve,
        private AbacGate $abac,
        private InstancePermissionGate $instanceGate,
    ) {}

    /**
     * Returns Response::allow() / Response::deny('reason') based on a layered check:
     *  1. Super admin bypass (handled at Gate::before, but kept here too for direct callers).
     *  2. User must be active.
     *  3. Tenant must be present and active.
     *  4. Resource (if provided) must belong to the same tenant.
     *  5. User must have the required permission (role/inherited/direct/wildcard),
     *     or an instance-level grant for this specific resource (ReBAC, 2.8).
     *  6. ABAC conditions attached to the permission must hold (2.4).
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

        if (! isset($permissions[$permission->value])) {
            // ReBAC fallback: an explicit grant on this resource instance bypasses
            // the missing static permission (but not tenant/active checks above).
            if ($resource !== null && $this->instanceGate->allows($user, $permission, $resource)) {
                return Response::allow();
            }

            return Response::deny("Missing permission: {$permission->value}");
        }

        // ABAC: a granted permission may still be narrowed by declarative conditions.
        if (! $this->abac->passes($user, $permission, $resource, $tenant)) {
            return Response::deny("Access conditions not met for: {$permission->value}");
        }

        return Response::allow();
    }
}
