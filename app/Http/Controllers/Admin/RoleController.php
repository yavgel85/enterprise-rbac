<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Authorization\CreateTenantRole;
use App\Actions\Authorization\DeleteTenantRole;
use App\Actions\Authorization\SyncRolePermissions;
use App\Actions\Authorization\UpdateTenantRole;
use App\Enums\Permission as PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\Tenant;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RoleController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $this->authorize('viewAny', Role::class);

        $roles = Role::query()
            ->where('tenant_id', $tenant->id)
            ->withCount(['users', 'permissions'])
            ->orderByDesc('level')
            ->get();

        return view('admin.roles.index', compact('roles', 'tenant'));
    }

    public function create(Tenant $tenant): View
    {
        $this->authorize('create', Role::class);

        return view('admin.roles.create', compact('tenant'));
    }

    public function store(Request $request, CreateTenantRole $action, Tenant $tenant): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'level' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        try {
            $role = $action->handle($request->user(), $tenant, $payload);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return redirect()->route('admin.roles.edit', [$tenant, $role])->with('status', 'Role created.');
    }

    public function edit(Tenant $tenant, Role $role): View
    {
        $this->authorize('update', $role);

        $role->load('permissions:id,slug');
        $permissionsByModule = collect(PermissionEnum::groupedByModule());

        return view('admin.roles.edit', [
            'tenant' => $tenant,
            'role' => $role,
            'permissionsByModule' => $permissionsByModule,
            'rolePermissionSlugs' => $role->permissions->pluck('slug')->all(),
        ]);
    }

    public function update(Request $request, UpdateTenantRole $action, Tenant $tenant, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $payload = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'level' => ['required', 'integer', 'min:0', 'max:100'],
        ]);

        try {
            $action->handle($request->user(), $role, $payload);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('status', 'Role updated.');
    }

    public function syncPermissions(Request $request, SyncRolePermissions $action, Tenant $tenant, Role $role): RedirectResponse
    {
        $this->authorize('syncPermissions', $role);

        $payload = $request->validate([
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);

        try {
            $action->handle($request->user(), $role, $payload['permissions'] ?? []);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Permissions updated.');
    }

    public function destroy(DeleteTenantRole $action, Tenant $tenant, Role $role): RedirectResponse
    {
        $this->authorize('delete', $role);

        try {
            $action->handle($role);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.roles.index', $tenant)->with('status', 'Role deleted.');
    }
}
