<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Audit\LogAuditEvent;
use App\Actions\Authorization\CreatePermissionCondition;
use App\Authorization\AbacGate;
use App\Enums\AuditAction;
use App\Enums\Permission as PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionCondition;
use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PermissionConditionController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $this->authorizeManage($request);

        $conditions = PermissionCondition::query()
            ->with(['permission:id,slug', 'role:id,name'])
            ->where(fn ($q) => $q->where('tenant_id', $tenant->id)->orWhereNull('tenant_id'))
            ->latest()
            ->get();

        return view('admin.permission-conditions.index', [
            'tenant' => $tenant,
            'conditions' => $conditions,
            'permissions' => Permission::query()->where('is_wildcard', false)->orderBy('slug')->get(['id', 'slug']),
            'roles' => Role::query()->where('tenant_id', $tenant->id)->orderBy('level', 'desc')->get(['id', 'name']),
        ]);
    }

    public function store(Request $request, CreatePermissionCondition $action, Tenant $tenant): RedirectResponse
    {
        $this->authorizeManage($request);

        $data = $request->validate([
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
            'role_id' => ['nullable', 'integer', 'exists:roles,id'],
            'conditions' => ['required', 'string'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if (isset($data['role_id'])) {
            $role = Role::findOrFail($data['role_id']);
            abort_unless($role->tenant_id === $tenant->id, 403);
        }

        $decoded = json_decode($data['conditions'], true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($decoded)) {
            throw ValidationException::withMessages([
                'conditions' => 'Conditions must be valid JSON describing a condition object.',
            ]);
        }

        $action->handle(
            $tenant,
            (int) $data['permission_id'],
            isset($data['role_id']) ? (int) $data['role_id'] : null,
            $decoded,
            $data['description'] ?? null,
        );

        return back()->with('status', 'Condition saved.');
    }

    public function destroy(Request $request, LogAuditEvent $audit, Tenant $tenant, PermissionCondition $condition): RedirectResponse
    {
        $this->authorizeManage($request);

        abort_unless($condition->tenant_id === $tenant->id, 403);

        $condition->delete();
        AbacGate::flushCache();
        $audit->handle(AuditAction::PermissionConditionDeleted, $condition);

        return back()->with('status', 'Condition removed.');
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->hasPermission(PermissionEnum::PermissionsAssign), 403);
    }
}
