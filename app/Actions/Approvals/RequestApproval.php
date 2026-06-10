<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\ApprovalStatus;
use App\Enums\AuditAction;
use App\Enums\DealStatus;
use App\Models\ApprovalRequest;
use App\Models\Deal;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

final readonly class RequestApproval
{
    public function __construct(private LogAuditEvent $audit) {}

    /**
     * @param  list<string>  $stepRoleSlugs  Ordered role slugs, one approval step each.
     */
    public function handle(Model $approvable, User $requester, array $stepRoleSlugs): ApprovalRequest
    {
        $tenantId = $approvable->tenant_id;

        $roleIds = Role::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('slug', $stepRoleSlugs)
            ->pluck('id', 'slug');

        return DB::transaction(function () use ($approvable, $requester, $stepRoleSlugs, $roleIds, $tenantId): ApprovalRequest {
            $request = ApprovalRequest::create([
                'tenant_id' => $tenantId,
                'approvable_type' => $approvable->getMorphClass(),
                'approvable_id' => $approvable->getKey(),
                'requested_by' => $requester->id,
                'status' => ApprovalStatus::Pending->value,
                'current_step' => 1,
            ]);

            foreach (array_values($stepRoleSlugs) as $index => $slug) {
                $request->steps()->create([
                    'step' => $index + 1,
                    'approver_role_id' => $roleIds[$slug] ?? null,
                ]);
            }

            if ($approvable instanceof Deal) {
                $approvable->update(['status' => DealStatus::PendingApproval->value]);
            }

            $this->audit->handle(AuditAction::ApprovalRequested, $approvable, [
                'approval_request_id' => $request->id,
                'steps' => $stepRoleSlugs,
            ]);

            return $request;
        });
    }
}
