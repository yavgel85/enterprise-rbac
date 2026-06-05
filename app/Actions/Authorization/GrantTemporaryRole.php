<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Actions\Audit\LogAuditEvent;
use App\Authorization\Constraints\RoleAssignmentConstraint;
use App\Enums\AuditAction;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class GrantTemporaryRole
{
    public function __construct(
        private RoleAssignmentConstraint $constraint,
        private ForgetUserPermissionsCache $forget,
        private LogAuditEvent $audit,
    ) {}

    /**
     * Grant a single role to a user that automatically expires after
     * $hours hours (JIT / sudo-style elevated access). Existing role
     * assignments are preserved.
     */
    public function handle(User $actor, User $member, int $roleId, int $hours): void
    {
        if ($hours < 1) {
            throw new DomainException('Temporary access must last at least one hour.');
        }

        DB::transaction(function () use ($actor, $member, $roleId, $hours) {
            $role = Role::query()
                ->withoutGlobalScopes()
                ->whereKey($roleId)
                ->where(function ($q) use ($member) {
                    $q->where('tenant_id', $member->tenant_id)
                        ->orWhere(fn ($q) => $q->whereNull('tenant_id')->where('is_system', true));
                })
                ->first(['id', 'slug', 'level', 'tenant_id']);

            if ($role === null) {
                throw new DomainException('Role is not available for this tenant.');
            }

            if (! $actor->is_super_admin && $role->level >= $actor->maxRoleLevel()) {
                throw new DomainException('You cannot grant a role equal to or higher than your own.');
            }

            // Separation-of-duties must hold for the resulting role set.
            $existingSlugs = $member->activeRoles()->pluck('roles.slug')->all();
            $this->constraint->assertValid([...$existingSlugs, $role->slug]);

            $expiresAt = CarbonImmutable::now()->addHours($hours);

            $member->roles()->syncWithoutDetaching([
                $role->id => [
                    'assigned_by' => $actor->id,
                    'assigned_at' => now(),
                    'expires_at' => $expiresAt,
                ],
            ]);

            $this->forget->forUser($member);

            $this->audit->handle(AuditAction::RolesAssigned, $member, [
                'role_id' => $role->id,
                'role_slug' => $role->slug,
                'temporary' => true,
                'expires_at' => $expiresAt->format(DATE_ATOM),
            ]);
        });
    }
}
