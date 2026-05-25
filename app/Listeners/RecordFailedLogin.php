<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Auth\Events\Failed;

final readonly class RecordFailedLogin
{
    public function __construct(private LogAuditEvent $audit) {}

    public function handle(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        $this->audit->handle(AuditAction::LoginFailed, metadata: [
            'email' => $email,
        ]);

        if ($email === null) {
            return;
        }

        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            return;
        }

        $maxAttempts = (int) config('rbac.lockout.max_attempts', 5);
        $duration = (int) config('rbac.lockout.duration_minutes', 15);

        $user->increment('failed_login_attempts');

        if ($user->failed_login_attempts >= $maxAttempts && ! $user->isLocked()) {
            $user->forceFill(['locked_until' => now()->addMinutes($duration)])->save();

            $this->audit->handle(AuditAction::AccountLocked, $user, [
                'duration_minutes' => $duration,
                'attempts' => $user->failed_login_attempts,
            ]);
        }
    }
}
