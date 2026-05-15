<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use Illuminate\Auth\Events\Failed;

final readonly class RecordFailedLogin
{
    public function __construct(private LogAuditEvent $audit) {}

    public function handle(Failed $event): void
    {
        $this->audit->handle(AuditAction::LoginFailed, metadata: [
            'email' => $event->credentials['email'] ?? null,
        ]);
    }
}
