<?php

declare(strict_types=1);

namespace App\Policies;

use App\Authorization\TenantAuthorizer;
use App\Enums\DealStatus;
use App\Enums\Permission;
use App\Models\Deal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final readonly class DealPolicy
{
    public function __construct(private TenantAuthorizer $auth) {}

    public function viewAny(User $user): Response
    {
        return $this->auth->allows($user, Permission::DealsView);
    }

    public function view(User $user, Deal $deal): Response
    {
        return $this->auth->allows($user, Permission::DealsView, resource: $deal);
    }

    public function create(User $user): Response
    {
        return $this->auth->allows($user, Permission::DealsCreate);
    }

    public function update(User $user, Deal $deal): Response
    {
        $check = $this->auth->allows($user, Permission::DealsUpdate, resource: $deal);
        if ($check->denied()) {
            return $check;
        }

        if ($deal->status !== DealStatus::Draft) {
            return Response::deny('Deals can only be edited while in draft status.');
        }

        $isOwner = $deal->owner_id === $user->id;
        $sameDepartment = $deal->department_id !== null
            && $deal->department_id === $user->department_id;

        return ($isOwner || $sameDepartment)
            ? Response::allow()
            : Response::deny('You can only update your own deals or deals in your department.');
    }

    public function delete(User $user, Deal $deal): Response
    {
        return $this->auth->allows($user, Permission::DealsDelete, resource: $deal);
    }

    public function approve(User $user, Deal $deal): Response
    {
        $check = $this->auth->allows($user, Permission::DealsApprove, resource: $deal);
        if ($check->denied()) {
            return $check;
        }

        $config = config('rbac.business_hours');
        $now = now();

        $weekdaysOnly = (bool) ($config['weekdays_only'] ?? true);
        if ($weekdaysOnly && ! $now->isWeekday()) {
            return Response::deny('Approvals are not allowed on weekends.');
        }

        $start = (int) ($config['start'] ?? 9);
        $end = (int) ($config['end'] ?? 18);

        return ($now->hour >= $start && $now->hour < $end)
            ? Response::allow()
            : Response::deny('Approvals are only allowed during business hours.');
    }

    public function export(User $user): Response
    {
        return $this->auth->allows($user, Permission::DealsExport);
    }
}
