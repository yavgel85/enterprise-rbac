<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

final readonly class ChangeOwnPassword
{
    public function __construct(private LogAuditEvent $audit) {}

    public function handle(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw new DomainException('Current password is incorrect.');
        }

        $user->forceFill([
            'password' => Hash::make($newPassword),
            'failed_login_attempts' => 0,
            'locked_until' => null,
        ])->save();

        // Sign other devices out, keep the current session alive.
        Auth::logoutOtherDevices($newPassword);

        $this->audit->handle(AuditAction::PasswordChangedBySelf, $user);
    }
}
