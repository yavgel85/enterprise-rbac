<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Actions\Approvals\DecideApprovalStep;
use App\Enums\ApprovalStatus;
use App\Enums\Permission as PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\ApprovalRequest;
use App\Models\Tenant;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ApprovalController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        abort_unless($request->user()->hasPermission(PermissionEnum::ApprovalsView), 403);

        $with = ['approvable', 'requester:id,name', 'steps.role:id,name', 'steps.decider:id,name'];

        $pending = ApprovalRequest::query()
            ->with($with)
            ->where('status', ApprovalStatus::Pending->value)
            ->latest()
            ->get();

        $decided = ApprovalRequest::query()
            ->with($with)
            ->whereIn('status', [ApprovalStatus::Approved->value, ApprovalStatus::Rejected->value])
            ->latest()
            ->limit(20)
            ->get();

        return view('crm.approvals.index', [
            'tenant' => $tenant,
            'pending' => $pending,
            'decided' => $decided,
        ]);
    }

    public function decide(Request $request, DecideApprovalStep $action, Tenant $tenant, ApprovalRequest $approvalRequest): RedirectResponse
    {
        abort_unless($request->user()->hasPermission(PermissionEnum::ApprovalsView), 403);

        $data = $request->validate([
            'decision' => ['required', 'in:approve,reject'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $action->handle(
                $approvalRequest,
                $request->user(),
                $data['decision'] === 'approve',
                $data['note'] ?? null,
            );
        } catch (DomainException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Approval decision recorded.');
    }
}
