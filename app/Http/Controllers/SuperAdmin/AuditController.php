<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        $query = AuditLog::query()
            ->with(['tenant:id,name,slug', 'user:id,name,email'])
            ->latest('created_at');

        if ($tenantId = $request->integer('tenant_id')) {
            $query->where('tenant_id', $tenantId);
        }

        if ($action = $request->string('action')->toString()) {
            $query->where('action', $action);
        }

        $logs = $query->paginate(50)->withQueryString();

        return view('super-admin.audit.index', [
            'logs' => $logs,
            'tenants' => Tenant::orderBy('name')->get(['id', 'name']),
            'actions' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
