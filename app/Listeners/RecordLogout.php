<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Models\User;
use Illuminate\Auth\Events\Logout;

final readonly class RecordLogout
{
    public function __construct(private LogAuditEvent $audit) {}

    public function handle(Logout $event): void
    {
        $user = $event->user;
        $this->audit->handle(AuditAction::Logout, $user instanceof User ? $user : null);
    }
}
