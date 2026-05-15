<?php

declare(strict_types=1);

namespace App\Policies;

use App\Authorization\TenantAuthorizer;
use App\Enums\Permission;
use App\Models\Invitation;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final readonly class InvitationPolicy
{
    public function __construct(private TenantAuthorizer $auth) {}

    public function viewAny(User $user): Response
    {
        return $this->auth->allows($user, Permission::UsersInvite);
    }

    public function create(User $user): Response
    {
        return $this->auth->allows($user, Permission::UsersInvite);
    }

    public function delete(User $user, Invitation $invitation): Response
    {
        return $this->auth->allows($user, Permission::UsersInvite, resource: $invitation);
    }
}
