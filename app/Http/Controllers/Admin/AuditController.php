<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Permission as PermissionEnum;
use App\Http\Controllers\Controller;
use App\Jobs\ExportAuditLog;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function index(Request $request, Tenant $tenant): View
    {
        abort_unless($request->user()->hasPermission(PermissionEnum::AuditView), 403);

        $query = AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->with('user:id,name,email')
            ->latest('created_at');

        if ($action = $request->string('action')->toString()) {
            $query->where('action', $action);
        }

        if ($userId = $request->integer('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($from = $request->date('from')) {
            $query->where('created_at', '>=', $from->startOfDay());
        }

        if ($to = $request->date('to')) {
            $query->where('created_at', '<=', $to->endOfDay());
        }

        $logs = $query->paginate(50)->withQueryString();

        // The filter dropdown rarely changes; cache it per-tenant (Improvement 4.7)
        // to avoid a DISTINCT scan over audit_logs on every page view.
        $actions = Cache::remember(
            tenant_cache_key('audit.actions', $tenant->id),
            now()->addMinutes(5),
            fn () => AuditLog::query()
                ->where('tenant_id', $tenant->id)
                ->distinct()
                ->pluck('action')
                ->sort()
                ->values()
                ->all(),
        );

        $users = User::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get(['id', 'name', 'email']);

        return view('admin.audit.index', compact('logs', 'tenant', 'actions', 'users'));
    }

    public function export(Request $request, Tenant $tenant): RedirectResponse
    {
        abort_unless($request->user()->hasPermission(PermissionEnum::AuditExport), 403);

        if (! $tenant->hasFeature('audit_export')) {
            return back()->with('error', 'Audit export feature is not enabled for this tenant.');
        }

        ExportAuditLog::dispatch($tenant->id, $request->user()->id, [
            'action' => $request->string('action')->toString() ?: null,
            'user_id' => $request->integer('user_id') ?: null,
            'from' => $request->string('from')->toString() ?: null,
            'to' => $request->string('to')->toString() ?: null,
        ]);

        return back()->with('status', 'Audit export queued — a download link will be emailed to you when it is ready.');
    }

    public function download(Request $request, Tenant $tenant, string $filename): StreamedResponse
    {
        abort_unless($request->user()->hasPermission(PermissionEnum::AuditExport), 403);

        // Reject path traversal; only the flat export filename is ever valid.
        abort_unless($filename === basename($filename), 404);

        $path = "audit-exports/{$tenant->id}/{$filename}";
        abort_unless(Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->download($path);
    }
}
