<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Models\Permission;
use App\Models\User;

final readonly class RevokeDirectPermission
{
    public function __construct(
        private ForgetUserPermissionsCache $forget,
        private LogAuditEvent $audit,
    ) {}

    public function handle(User $actor, User $member, Permission $permission): void
    {
        $member->directPermissions()->detach($permission->id);

        $this->forget->forUser($member);

        $this->audit->handle(AuditAction::PermissionRevoked, $member, [
            'permission_slug' => $permission->slug,
            'actor_id' => $actor->id,
        ]);
    }
}
