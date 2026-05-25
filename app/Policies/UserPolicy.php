<?php

declare(strict_types=1);

namespace App\Policies;

use App\Authorization\TenantAuthorizer;
use App\Enums\Permission;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final readonly class UserPolicy
{
    public function __construct(private TenantAuthorizer $auth) {}

    public function viewAny(User $user): Response
    {
        return $this->auth->allows($user, Permission::UsersView);
    }

    public function view(User $user, User $target): Response
    {
        return $this->auth->allows($user, Permission::UsersView, resource: $target);
    }

    public function create(User $user): Response
    {
        return $this->auth->allows($user, Permission::UsersCreate);
    }

    public function update(User $user, User $target): Response
    {
        return $this->auth->allows($user, Permission::UsersUpdate, resource: $target);
    }

    public function delete(User $user, User $target): Response
    {
        $check = $this->auth->allows($user, Permission::UsersDelete, resource: $target);
        if ($check->denied()) {
            return $check;
        }

        if ($user->id === $target->id) {
            return Response::deny('You cannot delete your own account.');
        }

        return Response::allow();
    }

    public function invite(User $user): Response
    {
        return $this->auth->allows($user, Permission::UsersInvite);
    }

    public function unlock(User $user, User $target): Response
    {
        return $this->auth->allows($user, Permission::UsersUnlock, resource: $target);
    }
}
