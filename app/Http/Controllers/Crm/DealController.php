<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Actions\Approvals\RequestApproval;
use App\Actions\Audit\LogAuditEvent;
use App\Actions\Authorization\GrantResourcePermission;
use App\Actions\Authorization\RevokeResourcePermission;
use App\Enums\ApprovalStatus;
use App\Enums\AuditAction;
use App\Enums\DealStage;
use App\Enums\DealStatus;
use App\Enums\Permission as PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\DealRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Department;
use App\Models\Permission;
use App\Models\ResourcePermission;
use App\Models\Tenant;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DealController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $this->authorize('viewAny', Deal::class);

        $deals = Deal::query()
            ->with(['company:id,name', 'owner:id,name'])
            ->latest()
            ->paginate(20);

        return view('crm.deals.index', compact('deals', 'tenant'));
    }

    public function create(Tenant $tenant): View
    {
        $this->authorize('create', Deal::class);

        return view('crm.deals.create', $this->formData($tenant));
    }

    public function store(DealRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('create', Deal::class);

        $deal = Deal::create($request->validated() + [
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('crm.deals.show', [$tenant, $deal])
            ->with('status', 'Deal created.');
    }

    public function show(Tenant $tenant, Deal $deal): View
    {
        $this->authorize('view', $deal);

        $deal->load(['company:id,name', 'contact:id,first_name,last_name', 'owner:id,name', 'department:id,name']);

        $instanceGrants = ResourcePermission::query()
            ->with(['user:id,name', 'permission:id,slug'])
            ->where('resource_type', $deal->getMorphClass())
            ->where('resource_id', $deal->getKey())
            ->get();

        return view('crm.deals.show', [
            'deal' => $deal,
            'tenant' => $tenant,
            'pendingApproval' => $deal->approvalRequests()->where('status', ApprovalStatus::Pending->value)->latest()->first(),
            'instanceGrants' => $instanceGrants,
            'assignableUsers' => User::query()->where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']),
            'instancePermissions' => Permission::query()
                ->whereIn('slug', ['deals.view', 'deals.update', 'deals.delete', 'deals.approve'])
                ->orderBy('slug')
                ->get(['id', 'slug']),
        ]);
    }

    public function edit(Tenant $tenant, Deal $deal): View
    {
        $this->authorize('update', $deal);

        return view('crm.deals.edit', $this->formData($tenant, $deal));
    }

    public function update(DealRequest $request, Tenant $tenant, Deal $deal): RedirectResponse
    {
        $this->authorize('update', $deal);

        $deal->update($request->validated());

        return redirect()->route('crm.deals.show', [$tenant, $deal])
            ->with('status', 'Deal updated.');
    }

    public function destroy(Tenant $tenant, Deal $deal): RedirectResponse
    {
        $this->authorize('delete', $deal);

        $deal->delete();

        return redirect()->route('crm.deals.index', $tenant)
            ->with('status', 'Deal deleted.');
    }

    public function approve(Request $request, Tenant $tenant, Deal $deal, RequestApproval $requestApproval, LogAuditEvent $audit): RedirectResponse
    {
        $this->authorize('approve', $deal);

        $threshold = (float) config('rbac.approvals.deal_threshold');

        if ((float) $deal->amount >= $threshold) {
            if ($deal->approvalRequests()->where('status', ApprovalStatus::Pending->value)->exists()) {
                return back()->with('error', 'This deal already has a pending approval request.');
            }

            $requestApproval->handle($deal, $request->user(), (array) config('rbac.approvals.deal_steps'));

            return redirect()->route('crm.deals.show', [$tenant, $deal])
                ->with('status', 'Deal submitted for multi-step approval.');
        }

        $deal->update([
            'stage' => DealStage::Won->value,
            'status' => DealStatus::Closed->value,
            'closed_at' => now(),
        ]);

        $audit->handle(AuditAction::DealApproved, $deal);

        return redirect()->route('crm.deals.show', [$tenant, $deal])
            ->with('status', 'Deal approved and closed.');
    }

    public function grantInstancePermission(Request $request, GrantResourcePermission $action, Tenant $tenant, Deal $deal): RedirectResponse
    {
        abort_unless($request->user()->hasPermission(PermissionEnum::PermissionsAssign), 403);

        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'permission_id' => ['required', 'integer', 'exists:permissions,id'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ]);

        $user = User::where('tenant_id', $tenant->id)->findOrFail($data['user_id']);
        $permission = Permission::findOrFail($data['permission_id']);

        $action->handle(
            $request->user(),
            $user,
            $permission,
            $deal,
            isset($data['expires_at']) ? Carbon::parse($data['expires_at']) : null,
        );

        return back()->with('status', 'Instance permission granted.');
    }

    public function revokeInstancePermission(Request $request, RevokeResourcePermission $action, Tenant $tenant, Deal $deal, ResourcePermission $resourcePermission): RedirectResponse
    {
        abort_unless($request->user()->hasPermission(PermissionEnum::PermissionsAssign), 403);
        abort_unless(
            $resourcePermission->resource_type === $deal->getMorphClass() && $resourcePermission->resource_id === $deal->getKey(),
            404,
        );

        $action->handle($resourcePermission);

        return back()->with('status', 'Instance permission revoked.');
    }

    private function formData(Tenant $tenant, ?Deal $deal = null): array
    {
        return [
            'tenant' => $tenant,
            'deal' => $deal,
            'companies' => Company::orderBy('name')->get(['id', 'name']),
            'contacts' => Contact::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
            'departments' => Department::orderBy('name')->get(['id', 'name']),
            'users' => User::query()->where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']),
        ];
    }
}
