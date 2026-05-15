<?php

declare(strict_types=1);

namespace App\Policies;

use App\Authorization\TenantAuthorizer;
use App\Enums\Permission;
use App\Models\Department;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final readonly class DepartmentPolicy
{
    public function __construct(private TenantAuthorizer $auth) {}

    public function viewAny(User $user): Response
    {
        return $this->auth->allows($user, Permission::DepartmentsView);
    }

    public function view(User $user, Department $department): Response
    {
        return $this->auth->allows($user, Permission::DepartmentsView, resource: $department);
    }

    public function create(User $user): Response
    {
        return $this->auth->allows($user, Permission::DepartmentsCreate);
    }

    public function update(User $user, Department $department): Response
    {
        return $this->auth->allows($user, Permission::DepartmentsUpdate, resource: $department);
    }

    public function delete(User $user, Department $department): Response
    {
        return $this->auth->allows($user, Permission::DepartmentsDelete, resource: $department);
    }
}
