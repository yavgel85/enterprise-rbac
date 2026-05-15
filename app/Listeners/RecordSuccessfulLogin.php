<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Auth\Events\Login;

final readonly class RecordSuccessfulLogin
{
    public function __construct(private LogAuditEvent $audit) {}

    public function handle(Login $event): void
    {
        $user = $event->user;

        if ($user instanceof User) {
            $user->forceFill([
                'last_login_at' => now(),
                'last_login_ip' => request()?->ip(),
            ])->saveQuietly();
        }

        $this->audit->handle(AuditAction::Login, $user instanceof User ? $user : null);
    }
}
