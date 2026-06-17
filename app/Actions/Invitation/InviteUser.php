<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\InvitationNotification;
use DomainException;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

final readonly class InviteUser
{
    public function __construct(private LogAuditEvent $audit) {}

    public function handle(
        User $actor,
        Tenant $tenant,
        string $email,
        ?Role $role = null,
        ?int $departmentId = null,
    ): Invitation {
        if ($role && ! $actor->is_super_admin) {
            $actorMaxLevel = $actor->maxRoleLevel();
            if ($role->level >= $actorMaxLevel) {
                throw new DomainException('You cannot invite a user with a role equal to or higher than your own.');
            }
        }

        if ($role && $role->tenant_id !== null && $role->tenant_id !== $tenant->id) {
            throw new DomainException('Role does not belong to the target tenant.');
        }

        $exists = User::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('email', $email)
            ->exists();

        if ($exists) {
            throw new DomainException('A user with this email already exists in the tenant.');
        }

        $ttl = (int) config('rbac.invitation_ttl_days', 7);

        $invitation = Invitation::create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'token' => Str::random(48),
            'role_id' => $role?->id,
            'department_id' => $departmentId,
            'invited_by' => $actor->id,
            'expires_at' => now()->addDays($ttl),
        ]);

        $this->audit->handle(AuditAction::InvitationSent, $invitation, [
            'email' => $email,
            'role_slug' => $role?->slug,
            'expires_at' => $invitation->expires_at->format(DATE_ATOM),
        ]);

        // Queued so the controller response is not blocked on mail delivery.
        Notification::route('mail', $email)->notify(
            new InvitationNotification($tenant->name, $invitation->token, $actor->name)
        );

        return $invitation;
    }
}
