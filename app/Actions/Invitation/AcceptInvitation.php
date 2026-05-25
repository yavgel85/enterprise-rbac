<?php

declare(strict_types=1);

namespace App\Actions\Invitation;

use App\Actions\Audit\LogAuditEvent;
use App\Actions\Authorization\RecordPasswordHistory;
use App\Enums\AuditAction;
use App\Models\Invitation;
use App\Models\User;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final readonly class AcceptInvitation
{
    public function __construct(
        private LogAuditEvent $audit,
        private RecordPasswordHistory $recordHistory,
    ) {}

    /**
     * @param  array{name: string, password: string}  $payload
     */
    public function handle(Invitation $invitation, array $payload): User
    {
        if (! $invitation->isPending()) {
            throw new DomainException('This invitation is no longer valid.');
        }

        return DB::transaction(function () use ($invitation, $payload) {
            $user = User::create([
                'tenant_id' => $invitation->tenant_id,
                'department_id' => $invitation->department_id,
                'name' => $payload['name'],
                'email' => $invitation->email,
                'password' => Hash::make($payload['password']),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);

            if ($invitation->role_id) {
                $user->roles()->attach($invitation->role_id, [
                    'assigned_by' => $invitation->invited_by,
                    'assigned_at' => now(),
                ]);
            }

            $this->recordHistory->handle($user, $payload['password']);

            $invitation->update(['accepted_at' => now()]);

            $this->audit->handle(AuditAction::InvitationAccepted, $user, [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
            ]);

            return $user;
        });
    }
}
