<?php

declare(strict_types=1);

namespace App\Actions\Approvals;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\ApprovalStatus;
use App\Enums\AuditAction;
use App\Enums\DealStage;
use App\Enums\DealStatus;
use App\Models\ApprovalRequest;
use App\Models\Deal;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class DecideApprovalStep
{
    public function __construct(private LogAuditEvent $audit) {}

    public function handle(ApprovalRequest $request, User $decider, bool $approve, ?string $note = null): ApprovalRequest
    {
        if (! $request->canBeDecidedBy($decider)) {
            throw new DomainException('You are not allowed to decide this approval step.');
        }

        return DB::transaction(function () use ($request, $decider, $approve, $note): ApprovalRequest {
            $step = $request->currentStep();

            $step->update([
                'decided_by' => $decider->id,
                'decided_at' => now(),
                'decision' => $approve ? 'approved' : 'rejected',
                'note' => $note,
            ]);

            if (! $approve) {
                $request->update(['status' => ApprovalStatus::Rejected->value]);
                $this->applyRejection($request);
                $this->audit->handle(AuditAction::ApprovalStepRejected, $request->approvable, [
                    'approval_request_id' => $request->id,
                    'step' => $step->step,
                ]);

                return $request->refresh();
            }

            $this->audit->handle(AuditAction::ApprovalStepApproved, $request->approvable, [
                'approval_request_id' => $request->id,
                'step' => $step->step,
            ]);

            $isLastStep = $request->current_step >= $request->steps()->count();

            if ($isLastStep) {
                $request->update(['status' => ApprovalStatus::Approved->value]);
                $this->applyApproval($request);
                $this->audit->handle(AuditAction::ApprovalCompleted, $request->approvable, [
                    'approval_request_id' => $request->id,
                ]);
            } else {
                $request->increment('current_step');
            }

            return $request->refresh();
        });
    }

    private function applyApproval(ApprovalRequest $request): void
    {
        $approvable = $request->approvable;

        if ($approvable instanceof Deal) {
            $approvable->update([
                'stage' => DealStage::Won->value,
                'status' => DealStatus::Closed->value,
                'closed_at' => now(),
            ]);
        }
    }

    private function applyRejection(ApprovalRequest $request): void
    {
        $approvable = $request->approvable;

        if ($approvable instanceof Deal) {
            $approvable->update(['status' => DealStatus::Active->value]);
        }
    }
}
