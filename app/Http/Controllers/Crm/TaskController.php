<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Enums\TaskStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\TaskRequest;
use App\Models\Task;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class TaskController extends Controller
{
    public function index(Tenant $tenant): View
    {
        $this->authorize('viewAny', Task::class);

        $tasks = Task::query()
            ->with(['assignee:id,name'])
            ->latest()
            ->paginate(20);

        return view('crm.tasks.index', compact('tasks', 'tenant'));
    }

    public function create(Tenant $tenant): View
    {
        $this->authorize('create', Task::class);

        return view('crm.tasks.create', [
            'tenant' => $tenant,
            'users' => $this->users($tenant),
        ]);
    }

    public function store(TaskRequest $request, Tenant $tenant): RedirectResponse
    {
        $this->authorize('create', Task::class);

        $task = Task::create($request->validated() + [
            'created_by' => $request->user()->id,
        ]);

        return redirect()->route('crm.tasks.show', [$tenant, $task])
            ->with('status', 'Task created.');
    }

    public function show(Tenant $tenant, Task $task): View
    {
        $this->authorize('view', $task);

        $task->load(['assignee:id,name', 'creator:id,name']);

        return view('crm.tasks.show', compact('task', 'tenant'));
    }

    public function edit(Tenant $tenant, Task $task): View
    {
        $this->authorize('update', $task);

        return view('crm.tasks.edit', [
            'tenant' => $tenant,
            'task' => $task,
            'users' => $this->users($tenant),
        ]);
    }

    public function update(TaskRequest $request, Tenant $tenant, Task $task): RedirectResponse
    {
        $this->authorize('update', $task);

        $task->update($request->validated());

        return redirect()->route('crm.tasks.show', [$tenant, $task])
            ->with('status', 'Task updated.');
    }

    public function destroy(Tenant $tenant, Task $task): RedirectResponse
    {
        $this->authorize('delete', $task);

        $task->delete();

        return redirect()->route('crm.tasks.index', $tenant)
            ->with('status', 'Task deleted.');
    }

    public function complete(Tenant $tenant, Task $task, LogAuditEvent $audit): RedirectResponse
    {
        $this->authorize('complete', $task);

        $task->update([
            'status' => TaskStatus::Done->value,
            'completed_at' => now(),
        ]);

        $audit->handle(AuditAction::TaskCompleted, $task);

        return redirect()->route('crm.tasks.show', [$tenant, $task])
            ->with('status', 'Task completed.');
    }

    private function users(Tenant $tenant)
    {
        return User::query()->where('tenant_id', $tenant->id)->orderBy('name')->get(['id', 'name']);
    }
}
