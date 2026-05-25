<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class SetUserPassword
{
    public function __construct(
        private LogAuditEvent $audit,
        private AssertPasswordNotReused $assertNotReused,
        private RecordPasswordHistory $recordHistory,
    ) {}

    public function handle(User $actor, User $target, string $newPassword): void
    {
        if ($actor->id === $target->id) {
            throw new DomainException('Use the profile page to change your own password.');
        }

        if (! $actor->is_super_admin) {
            if ($target->is_super_admin) {
                throw new DomainException('Only super-admins can change a super-admin password.');
            }

            if ($actor->tenant_id !== $target->tenant_id) {
                throw new DomainException('Cross-tenant password change is not allowed.');
            }

            $actorLevel = (int) ($actor->roles->max('level') ?? 0);
            $targetLevel = (int) ($target->roles->max('level') ?? 0);

            if ($targetLevel >= $actorLevel) {
                throw new DomainException('Cannot change the password of a user at or above your role level.');
            }
        }

        $this->assertNotReused->handle($target, $newPassword);

        DB::transaction(function () use ($actor, $target, $newPassword): void {
            $target->forceFill([
                'password' => Hash::make($newPassword),
                'failed_login_attempts' => 0,
                'locked_until' => null,
            ])->save();

            $this->recordHistory->handle($target, $newPassword);

            DB::table('sessions')->where('user_id', $target->id)->delete();

            $this->audit->handle(AuditAction::PasswordChangedByAdmin, $target, [
                'changed_by' => $actor->id,
                'is_super_admin_action' => (bool) $actor->is_super_admin,
            ]);
        });
    }
}
