<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ObservabilityController extends Controller
{
    public function index(): View
    {
        $now = now();
        $dayAgo = $now->copy()->subDay();

        $stats = [
            'tenants_active' => Tenant::query()->where('is_active', true)->count(),
            'tenants_total' => Tenant::query()->count(),
            'users_total' => User::query()->count(),
            'users_locked' => User::query()->whereNotNull('locked_until')->where('locked_until', '>', $now)->count(),
            'audit_24h' => AuditLog::query()->where('created_at', '>=', $dayAgo)->count(),
            'failed_logins_24h' => AuditLog::query()
                ->where('action', AuditAction::LoginFailed->value)
                ->where('created_at', '>=', $dayAgo)
                ->count(),
            'denied_24h' => AuditLog::query()
                ->where('action', AuditAction::PermissionDenied->value)
                ->where('created_at', '>=', $dayAgo)
                ->count(),
        ];

        $failedJobs = $this->failedJobsCount();

        $eventsByDay = AuditLog::query()
            ->where('created_at', '>=', $now->copy()->subDays(14)->startOfDay())
            ->selectRaw('date(created_at) as day, count(*) as total')
            ->groupBy('day')
            ->orderBy('day')
            ->pluck('total', 'day');

        $topActions = AuditLog::query()
            ->where('created_at', '>=', $dayAgo)
            ->selectRaw('action, count(*) as total')
            ->groupBy('action')
            ->orderByDesc('total')
            ->limit(8)
            ->pluck('total', 'action');

        $securityFeed = AuditLog::query()
            ->whereIn('action', [
                AuditAction::LoginFailed->value,
                AuditAction::PermissionDenied->value,
                AuditAction::AccountLocked->value,
            ])
            ->with(['tenant:id,name', 'user:id,email'])
            ->latest('created_at')
            ->limit(15)
            ->get();

        return view('super-admin.observability.index', compact(
            'stats', 'failedJobs', 'eventsByDay', 'topActions', 'securityFeed'
        ));
    }

    private function failedJobsCount(): int
    {
        try {
            if (! DB::getSchemaBuilder()->hasTable('failed_jobs')) {
                return 0;
            }

            return DB::table('failed_jobs')->count();
        } catch (\Throwable) {
            return 0;
        }
    }
}
