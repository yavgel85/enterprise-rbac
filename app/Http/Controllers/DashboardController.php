<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\Permission;
use App\Models\Activity;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function show(Tenant $tenant): View
    {
        $stats = [
            'companies' => Company::count(),
            'contacts' => Contact::count(),
            'deals' => Deal::count(),
            'activities' => Activity::count(),
            'users' => User::query()->where('tenant_id', $tenant->id)->count(),
        ];

        $recentDeals = Deal::query()
            ->with(['company:id,name', 'owner:id,name'])
            ->latest()
            ->limit(5)
            ->get();

        return view('dashboard', [
            'tenant' => $tenant,
            'stats' => $stats,
            'recentDeals' => $recentDeals,
            'canViewFeed' => request()->user()->hasPermission(Permission::AuditView),
        ]);
    }

    /**
     * JSON feed of recent tenant activity, polled by the dashboard widget (3.8).
     */
    public function activityFeed(Request $request, Tenant $tenant): JsonResponse
    {
        abort_unless($request->user()->hasPermission(Permission::AuditView), 403);

        $query = AuditLog::query()
            ->where('tenant_id', $tenant->id)
            ->with('user:id,name,email')
            ->latest('created_at');

        if ($after = $request->integer('after')) {
            $query->where('id', '>', $after);
        }

        $events = $query->limit(20)->get()->map(fn (AuditLog $log) => [
            'id' => $log->id,
            'action' => $log->action,
            'user' => $log->user?->name ?? $log->user?->email ?? 'system',
            'target' => $log->auditable_type
                ? class_basename($log->auditable_type).'#'.$log->auditable_id
                : null,
            'at' => $log->created_at?->diffForHumans(),
            'timestamp' => $log->created_at?->toIso8601String(),
        ]);

        return response()->json([
            'events' => $events,
            'latest_id' => $events->max('id') ?? $request->integer('after'),
        ]);
    }
}
