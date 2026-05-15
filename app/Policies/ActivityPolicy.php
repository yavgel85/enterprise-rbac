<?php

declare(strict_types=1);

namespace App\Policies;

use App\Authorization\TenantAuthorizer;
use App\Enums\Permission;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final readonly class ActivityPolicy
{
    public function __construct(private TenantAuthorizer $auth) {}

    public function viewAny(User $user): Response
    {
        return $this->auth->allows($user, Permission::ActivitiesView);
    }

    public function view(User $user, Activity $activity): Response
    {
        return $this->auth->allows($user, Permission::ActivitiesView, resource: $activity);
    }

    public function create(User $user): Response
    {
        return $this->auth->allows($user, Permission::ActivitiesCreate);
    }

    public function update(User $user, Activity $activity): Response
    {
        return $this->auth->allows($user, Permission::ActivitiesUpdate, resource: $activity);
    }

    public function delete(User $user, Activity $activity): Response
    {
        return $this->auth->allows($user, Permission::ActivitiesDelete, resource: $activity);
    }
}
