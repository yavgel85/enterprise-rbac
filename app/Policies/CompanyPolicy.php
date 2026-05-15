<?php

declare(strict_types=1);

namespace App\Policies;

use App\Authorization\TenantAuthorizer;
use App\Enums\Permission;
use App\Models\Company;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final readonly class CompanyPolicy
{
    public function __construct(private TenantAuthorizer $auth) {}

    public function viewAny(User $user): Response
    {
        return $this->auth->allows($user, Permission::CompaniesView);
    }

    public function view(User $user, Company $company): Response
    {
        return $this->auth->allows($user, Permission::CompaniesView, resource: $company);
    }

    public function create(User $user): Response
    {
        return $this->auth->allows($user, Permission::CompaniesCreate);
    }

    public function update(User $user, Company $company): Response
    {
        return $this->auth->allows($user, Permission::CompaniesUpdate, resource: $company);
    }

    public function delete(User $user, Company $company): Response
    {
        return $this->auth->allows($user, Permission::CompaniesDelete, resource: $company);
    }
}
