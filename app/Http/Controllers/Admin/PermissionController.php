<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Authorization\GrantDirectPermission;
use App\Actions\Authorization\RevokeDirectPermission;
use App\Enums\DirectPermissionType;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PermissionController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $this->authorize('viewAny', Permission::class);

        $permissions = Permission::query()
            ->with('module:id,name,slug')
            ->orderBy('module_id')
            ->orderBy('slug')
            ->get();

        return view('admin.permissions.index', [
            'tenant' => $tenant,
            'permissionsByModule' => $permissions->groupBy(fn ($p) => $p->module->name),
        ]);
    }

    public function userEdit(Tenant $tenant, User $user): View
    {
        $this->authorize('update', $user);

        $user->load('directPermissions:id,slug');

        return view('admin.permissions.user-edit', [
            'tenant' => $tenant,
            'user' => $user,
            'allPermissions' => Permission::query()->orderBy('slug')->get(['id', 'slug']),
            'directMap' => $user->directPermissions->keyBy('id'),
        ]);
    }

    public function userGrant(Request $request, GrantDirectPermission $action, Tenant $tenant, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $payload = $request->validate([
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
            'type' => ['required', 'in:grant,deny'],
            'expires_at' => ['nullable', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $permission = Permission::findOrFail($payload['permission_id']);

        try {
            $action->handle(
                $request->user(),
                $user,
                $permission,
                DirectPermissionType::from($payload['type']),
                isset($payload['expires_at']) ? Carbon::parse($payload['expires_at']) : null,
                $payload['reason'] ?? null,
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Direct permission saved.');
    }

    public function userRevoke(Request $request, RevokeDirectPermission $action, Tenant $tenant, User $user, Permission $permission): RedirectResponse
    {
        $this->authorize('update', $user);

        $action->handle($request->user(), $user, $permission);

        return back()->with('status', 'Direct permission revoked.');
    }
}
