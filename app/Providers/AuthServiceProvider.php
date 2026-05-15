<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\Activity;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Department;
use App\Models\Invitation;
use App\Models\Role;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use App\Policies\ActivityPolicy;
use App\Policies\CompanyPolicy;
use App\Policies\ContactPolicy;
use App\Policies\DealPolicy;
use App\Policies\DepartmentPolicy;
use App\Policies\InvitationPolicy;
use App\Policies\RolePolicy;
use App\Policies\TaskPolicy;
use App\Policies\TenantPolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected array $policies = [
        Activity::class => ActivityPolicy::class,
        Company::class => CompanyPolicy::class,
        Contact::class => ContactPolicy::class,
        Deal::class => DealPolicy::class,
        Department::class => DepartmentPolicy::class,
        Invitation::class => InvitationPolicy::class,
        Role::class => RolePolicy::class,
        Task::class => TaskPolicy::class,
        Tenant::class => TenantPolicy::class,
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        Gate::before(fn (User $user) => $user->is_super_admin ? true : null);
    }
}
