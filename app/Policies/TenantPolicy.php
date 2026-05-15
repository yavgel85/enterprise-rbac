<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

final readonly class TenantPolicy
{
    public function viewAny(User $user): Response
    {
        return $user->is_super_admin
            ? Response::allow()
            : Response::deny('Only super admins can manage tenants.');
    }

    public function manage(User $user): Response
    {
        return $user->is_super_admin
            ? Response::allow()
            : Response::deny('Only super admins can manage tenants.');
    }
}
