<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Authorization\AssignRolesToUser;
use App\Actions\Authorization\UnlockUserAccount;
use App\Actions\Invitation\InviteUser;
use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $this->authorize('viewAny', User::class);

        $users = User::query()
            ->with(['department:id,name', 'roles:id,name,slug,level'])
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->paginate(20);

        $invitations = Invitation::query()
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        return view('admin.users.index', compact('users', 'invitations', 'tenant'));
    }

    public function show(Tenant $tenant, User $user): View
    {
        $this->authorize('view', $user);

        $user->load(['roles', 'department:id,name', 'directPermissions']);

        $allRoles = Role::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('level', 'desc')
            ->get(['id', 'name', 'slug', 'level']);

        return view('admin.users.show', compact('user', 'tenant', 'allRoles'));
    }

    public function syncRoles(Request $request, AssignRolesToUser $assign, Tenant $tenant, User $user): RedirectResponse
    {
        $this->authorize('update', $user);

        $payload = $request->validate([
            'role_ids' => ['array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        try {
            $assign->handle($request->user(), $user, $payload['role_ids'] ?? []);
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Roles updated.');
    }

    public function unlock(UnlockUserAccount $unlock, Tenant $tenant, User $user): RedirectResponse
    {
        $this->authorize('unlock', $user);

        $unlock->handle(request()->user(), $user);

        return back()->with('status', 'Account unlocked.');
    }

    public function invite(Request $request, InviteUser $invite, Tenant $tenant): RedirectResponse
    {
        $this->authorize('invite', User::class);

        $payload = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
        ]);

        $role = $payload['role_id'] ? Role::findOrFail($payload['role_id']) : null;

        try {
            $invite->handle(
                $request->user(),
                $tenant,
                $payload['email'],
                $role,
                $payload['department_id'] ?? null,
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()->with('status', 'Invitation sent.');
    }
}
