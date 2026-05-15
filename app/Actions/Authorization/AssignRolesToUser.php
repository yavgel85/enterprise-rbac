<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Actions\Audit\LogAuditEvent;
use App\Authorization\Constraints\RoleAssignmentConstraint;
use App\Enums\AuditAction;
use App\Models\Role;
use App\Models\User;
use DateTimeInterface;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class AssignRolesToUser
{
    public function __construct(
        private RoleAssignmentConstraint $constraint,
        private ForgetUserPermissionsCache $forget,
        private LogAuditEvent $audit,
    ) {}

    /**
     * Replaces the user's role assignments with the provided list.
     *
     * @param  list<int>  $roleIds
     */
    public function handle(User $actor, User $member, array $roleIds, ?DateTimeInterface $expiresAt = null): void
    {
        DB::transaction(function () use ($actor, $member, $roleIds, $expiresAt) {
            if ($member->tenant_id === null && ! $actor->is_super_admin) {
                throw new DomainException('Cannot assign roles to a user without a tenant.');
            }

            $roles = Role::query()
                ->withoutGlobalScopes()
                ->whereKey($roleIds)
                ->where(function ($q) use ($member) {
                    $q->where('tenant_id', $member->tenant_id)
                        ->orWhere(function ($q) {
                            $q->whereNull('tenant_id')->where('is_system', true);
                        });
                })
                ->get(['id', 'slug', 'level', 'is_system', 'tenant_id']);

            if ($roles->count() !== count($roleIds)) {
                throw new DomainException('One or more roles are not available for this tenant.');
            }

            $this->constraint->assertValid($roles->pluck('slug')->all());

            if (! $actor->is_super_admin) {
                $actorMaxLevel = $actor->maxRoleLevel();
                $requestedMax = (int) $roles->max('level');

                if ($requestedMax >= $actorMaxLevel) {
                    throw new DomainException('You cannot assign a role equal to or higher than your own.');
                }
            }

            $member->roles()->sync(
                $roles->mapWithKeys(fn (Role $role) => [
                    $role->id => [
                        'assigned_by' => $actor->id,
                        'assigned_at' => now(),
                        'expires_at' => $expiresAt,
                    ],
                ])->all()
            );

            $this->forget->forUser($member);

            $this->audit->handle(AuditAction::RolesAssigned, $member, [
                'role_ids' => $roleIds,
                'role_slugs' => $roles->pluck('slug')->all(),
                'expires_at' => $expiresAt?->format(DATE_ATOM),
            ]);
        });
    }
}
