<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Enums\DirectPermissionType;
use App\Models\Permission;
use App\Models\User;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class GrantDirectPermission
{
    public function __construct(
        private ForgetUserPermissionsCache $forget,
        private LogAuditEvent $audit,
    ) {}

    public function handle(
        User $actor,
        User $member,
        Permission $permission,
        DirectPermissionType $type = DirectPermissionType::Grant,
        ?DateTimeInterface $expiresAt = null,
        ?string $reason = null,
    ): void {
        if (! $actor->is_super_admin && ! $actor->hasPermission($permission->slug)) {
            throw new DomainException('You cannot grant a permission that you do not hold yourself.');
        }

        DB::transaction(function () use ($actor, $member, $permission, $type, $expiresAt, $reason) {
            $member->directPermissions()->syncWithoutDetaching([
                $permission->id => [
                    'type' => $type->value,
                    'expires_at' => $expiresAt,
                    'assigned_by' => $actor->id,
                    'reason' => $reason,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            ]);

            $member->directPermissions()->updateExistingPivot($permission->id, [
                'type' => $type->value,
                'expires_at' => $expiresAt,
                'assigned_by' => $actor->id,
                'reason' => $reason,
                'updated_at' => now(),
            ]);

            $this->forget->forUser($member);

            $this->audit->handle(AuditAction::PermissionGranted, $member, [
                'permission_slug' => $permission->slug,
                'type' => $type->value,
                'expires_at' => $expiresAt?->format(DATE_ATOM),
                'reason' => $reason,
            ]);
        });
    }
}
