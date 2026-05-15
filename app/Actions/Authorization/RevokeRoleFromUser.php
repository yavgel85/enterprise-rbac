<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Models\Role;
use App\Models\User;

final readonly class RevokeRoleFromUser
{
    public function __construct(
        private ForgetUserPermissionsCache $forget,
        private LogAuditEvent $audit,
    ) {}

    public function handle(User $actor, User $member, Role $role): void
    {
        $member->roles()->detach($role->id);

        $this->forget->forUser($member);

        $this->audit->handle(AuditAction::RoleRevoked, $member, [
            'role_id' => $role->id,
            'role_slug' => $role->slug,
            'actor_id' => $actor->id,
        ]);
    }
}
