<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Enums\Permission as PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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

        $logs = $query->paginate(50)->withQueryString();

        $actions = AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->distinct()
            ->pluck('action')
            ->sort()
            ->values();

        return view('admin.audit.index', compact('logs', 'tenant', 'actions'));
    }

    public function export(Request $request, Tenant $tenant): StreamedResponse|RedirectResponse
    {
        abort_unless($request->user()->hasPermission(PermissionEnum::AuditExport), 403);

        if (! $tenant->hasFeature('audit_export')) {
            return back()->with('error', 'Audit export feature is not enabled for this tenant.');
        }

        return response()->streamDownload(function () use ($tenant) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'action', 'user', 'auditable', 'ip', 'created_at']);

            AuditLog::query()
                ->where('tenant_id', $tenant->id)
                ->with('user:id,email')
                ->orderBy('id')
                ->lazyById()
                ->each(function (AuditLog $log) use ($out) {
                    fputcsv($out, [
                        $log->id,
                        $log->action,
                        $log->user?->email,
                        $log->auditable_type ? $log->auditable_type.'#'.$log->auditable_id : null,
                        $log->ip_address,
                        $log->created_at?->format('Y-m-d H:i:s'),
                    ]);
                });

            fclose($out);
        }, "audit-{$tenant->slug}-".now()->format('Ymd-His').'.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}
