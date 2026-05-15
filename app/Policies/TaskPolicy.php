<?php

declare(strict_types=1);

namespace App\Policies;

use App\Authorization\TenantAuthorizer;
use App\Enums\Permission;
use App\Models\Task;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final readonly class TaskPolicy
{
    public function __construct(private TenantAuthorizer $auth) {}

    public function viewAny(User $user): Response
    {
        return $this->auth->allows($user, Permission::TasksView);
    }

    public function view(User $user, Task $task): Response
    {
        return $this->auth->allows($user, Permission::TasksView, resource: $task);
    }

    public function create(User $user): Response
    {
        return $this->auth->allows($user, Permission::TasksCreate);
    }

    public function update(User $user, Task $task): Response
    {
        return $this->auth->allows($user, Permission::TasksUpdate, resource: $task);
    }

    public function delete(User $user, Task $task): Response
    {
        return $this->auth->allows($user, Permission::TasksDelete, resource: $task);
    }

    public function complete(User $user, Task $task): Response
    {
        $check = $this->auth->allows($user, Permission::TasksComplete, resource: $task);
        if ($check->denied()) {
            return $check;
        }

        $isAssignee = $task->assignee_id === $user->id;
        $isCreator = $task->created_by === $user->id;

        return ($isAssignee || $isCreator)
            ? Response::allow()
            : Response::deny('Only the assignee or creator can complete this task.');
    }
}
