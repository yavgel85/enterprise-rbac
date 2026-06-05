<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Authorization\ApplyPermissionGroupToRole;
use App\Actions\Authorization\CloneRole;
use App\Actions\Authorization\CreateTenantRole;
use App\Actions\Authorization\DeleteTenantRole;
use App\Actions\Authorization\SetRoleParent;
use App\Actions\Authorization\SyncRolePermissions;
use App\Actions\Authorization\UpdateTenantRole;
use App\Enums\Permission as PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\PermissionGroup;
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

        $cloneable = Role::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('level')
            ->get(['id', 'name', 'slug', 'level']);

        return view('admin.roles.create', compact('tenant', 'cloneable'));
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

    public function clone(Request $request, CloneRole $action, Tenant $tenant, Role $role): RedirectResponse
    {
        $this->authorize('create', Role::class);

        $payload = $request->validate([
            'name' => ['nullable', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255'],
            'level' => ['nullable', 'integer', 'min:0', 'max:100'],
        ]);

        try {
            $clone = $action->handle($request->user(), $tenant, $role, array_filter($payload, fn ($v) => $v !== null));
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('admin.roles.edit', [$tenant, $clone])
            ->with('status', "Cloned “{$role->name}”. Adjust the new role below.");
    }

    public function edit(Tenant $tenant, Role $role): View
    {
        $this->authorize('update', $role);

        $role->load('permissions:id,slug', 'parent:id,name');
        $permissionsByModule = collect(PermissionEnum::groupedByModule());

        $parentCandidates = Role::query()
            ->where('tenant_id', $tenant->id)
            ->whereKeyNot($role->id)
            ->orderByDesc('level')
            ->get(['id', 'name', 'slug', 'level']);

        $permissionGroups = PermissionGroup::query()
            ->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenant->id))
            ->withCount('permissions')
            ->orderBy('name')
            ->get();

        $affectedUsers = $role->users()
            ->select(['users.id', 'users.name', 'users.email'])
            ->orderBy('users.name')
            ->limit(50)
            ->get();
        $affectedUserCount = $role->users()->count();

        return view('admin.roles.edit', [
            'tenant' => $tenant,
            'role' => $role,
            'permissionsByModule' => $permissionsByModule,
            'rolePermissionSlugs' => $role->permissions->pluck('slug')->all(),
            'parentCandidates' => $parentCandidates,
            'permissionGroups' => $permissionGroups,
            'affectedUsers' => $affectedUsers,
            'affectedUserCount' => $affectedUserCount,
        ]);
    }

    public function applyGroup(Request $request, ApplyPermissionGroupToRole $action, Tenant $tenant, Role $role): RedirectResponse
    {
        $this->authorize('syncPermissions', $role);

        $payload = $request->validate([
            'permission_group_id' => ['required', 'integer', 'exists:permission_groups,id'],
        ]);

        $group = PermissionGroup::query()
            ->where(fn ($q) => $q->whereNull('tenant_id')->orWhere('tenant_id', $tenant->id))
            ->findOrFail($payload['permission_group_id']);

        try {
            $action->handle($request->user(), $role, $group);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', "Applied “{$group->name}” to the role.");
    }

    public function syncParent(Request $request, SetRoleParent $action, Tenant $tenant, Role $role): RedirectResponse
    {
        $this->authorize('update', $role);

        $payload = $request->validate([
            'parent_id' => ['nullable', 'integer', 'exists:roles,id'],
        ]);

        try {
            $action->handle($request->user(), $role, $payload['parent_id'] ?? null);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Role inheritance updated.');
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

        $before = $role->permissions()->pluck('slug')->all();

        try {
            $action->handle($request->user(), $role, $payload['permissions'] ?? []);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        $after = $role->permissions()->pluck('slug')->all();
        $added = array_values(array_diff($after, $before));
        $removed = array_values(array_diff($before, $after));

        return back()
            ->with('status', 'Permissions updated.')
            ->with('perm_diff', ['added' => $added, 'removed' => $removed]);
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
