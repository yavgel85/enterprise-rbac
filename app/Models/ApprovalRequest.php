<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ApprovalStatus;
use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ApprovalRequest extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'approvable_type',
        'approvable_id',
        'requested_by',
        'status',
        'current_step',
        'payload',
    ];

    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'current_step' => 'integer',
            'payload' => 'array',
        ];
    }

    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function steps(): HasMany
    {
        return $this->hasMany(ApprovalStep::class)->orderBy('step');
    }

    public function currentStep(): ?ApprovalStep
    {
        return $this->steps->firstWhere('step', $this->current_step);
    }

    public function isPending(): bool
    {
        return $this->status === ApprovalStatus::Pending;
    }

    /**
     * Whether $user is allowed to decide the *current* step.
     * The original requester is always excluded (separation of duties).
     */
    public function canBeDecidedBy(User $user): bool
    {
        if (! $this->isPending() || $this->requested_by === $user->id) {
            return false;
        }

        $step = $this->currentStep();

        if ($step === null) {
            return false;
        }

        if ($user->is_super_admin) {
            return true;
        }

        if ($step->approver_role_id === null) {
            return true;
        }

        return $user->activeRoles()->where('roles.id', $step->approver_role_id)->exists();
    }

    /**
     * Pending requests in the user's tenant whose current step the user may decide.
     */
    public static function pendingForUser(User $user): Builder
    {
        $roleIds = $user->activeRoles()->pluck('roles.id')->all();

        return static::query()
            ->where('status', ApprovalStatus::Pending->value)
            ->where('requested_by', '!=', $user->id)
            ->whereHas('steps', function (Builder $query) use ($roleIds): void {
                $query->whereColumn('approval_steps.step', 'approval_requests.current_step')
                    ->where(function (Builder $q) use ($roleIds): void {
                        $q->whereNull('approver_role_id');

                        if ($roleIds !== []) {
                            $q->orWhereIn('approver_role_id', $roleIds);
                        }
                    });
            });
    }
}
