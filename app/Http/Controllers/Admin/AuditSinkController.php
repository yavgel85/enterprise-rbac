<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Enums\Permission as PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\AuditSink;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AuditSinkController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        $this->authorizeManage($request);

        return view('admin.audit-sinks.index', [
            'tenant' => $tenant,
            'sinks' => AuditSink::query()->where('tenant_id', $tenant->id)->latest()->get(),
            'actions' => collect(AuditAction::cases())->map(fn (AuditAction $a) => $a->value)->sort()->values(),
        ]);
    }

    public function store(Request $request, LogAuditEvent $audit, Tenant $tenant): RedirectResponse
    {
        $this->authorizeManage($request);

        $data = $this->validateSink($request);

        $sink = AuditSink::create([
            'tenant_id' => $tenant->id,
            'name' => $data['name'],
            'type' => 'webhook',
            'endpoint' => $data['endpoint'],
            'secret' => $data['secret'] ?? null,
            'events' => $this->normaliseEvents($data['events'] ?? null),
            'is_active' => $request->boolean('is_active', true),
        ]);

        $audit->handle(AuditAction::AuditSinkCreated, $sink, ['endpoint' => $sink->endpoint]);

        return back()->with('status', 'Audit sink created.');
    }

    public function update(Request $request, LogAuditEvent $audit, Tenant $tenant, AuditSink $auditSink): RedirectResponse
    {
        $this->authorizeManage($request);
        abort_unless($auditSink->tenant_id === $tenant->id, 403);

        $data = $this->validateSink($request);

        $auditSink->update([
            'name' => $data['name'],
            'endpoint' => $data['endpoint'],
            'secret' => $data['secret'] ?: $auditSink->secret,
            'events' => $this->normaliseEvents($data['events'] ?? null),
            'is_active' => $request->boolean('is_active'),
        ]);

        $audit->handle(AuditAction::AuditSinkUpdated, $auditSink);

        return back()->with('status', 'Audit sink updated.');
    }

    public function destroy(Request $request, LogAuditEvent $audit, Tenant $tenant, AuditSink $auditSink): RedirectResponse
    {
        $this->authorizeManage($request);
        abort_unless($auditSink->tenant_id === $tenant->id, 403);

        $auditSink->delete();
        $audit->handle(AuditAction::AuditSinkDeleted, null, ['audit_sink_id' => $auditSink->id]);

        return back()->with('status', 'Audit sink removed.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validateSink(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'endpoint' => ['required', 'url', 'max:2048'],
            'secret' => ['nullable', 'string', 'max:255'],
            'events' => ['nullable', 'array'],
            'events.*' => [Rule::in(array_map(fn (AuditAction $a) => $a->value, AuditAction::cases()))],
            'is_active' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<int, string>|null  $events
     * @return array<int, string>|null
     */
    private function normaliseEvents(?array $events): ?array
    {
        if (empty($events)) {
            return null;
        }

        return array_values(array_unique($events));
    }

    private function authorizeManage(Request $request): void
    {
        abort_unless($request->user()->hasPermission(PermissionEnum::AuditManage), 403);
    }
}
