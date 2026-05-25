<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Models\User;

final readonly class UnlockUserAccount
{
    public function __construct(private LogAuditEvent $audit) {}

    public function handle(User $actor, User $target): void
    {
        if ($target->failed_login_attempts === 0 && $target->locked_until === null) {
            return;
        }

        $target->forceFill([
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        $this->audit->handle(AuditAction::AccountUnlocked, $target, [
            'unlocked_by' => $actor->id,
        ]);
    }
}
