<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Permission;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Support\Facades\DB;

final readonly class PermissionUsageReport
{
    public const CACHE_KEY = 'rbac:usage:report';

    public function __construct(private Cache $cache) {}

    /**
     * Per-permission usage stats keyed by slug:
     *   ['deals.view' => ['granted_roles' => 3, 'granted_users' => 1, 'denied' => 12], ...]
     *
     * @return array<string, array{granted_roles: int, granted_users: int, denied: int}>
     */
    public function handle(bool $fresh = false): array
    {
        $ttl = (int) config('rbac.usage.cache_ttl', 86400);

        if ($fresh) {
            $this->cache->forget(self::CACHE_KEY);
        }

        return $this->cache->remember(self::CACHE_KEY, $ttl, fn () => $this->compute());
    }

    /**
     * @return array<string, array{granted_roles: int, granted_users: int, denied: int}>
     */
    private function compute(): array
    {
        $permissions = Permission::query()->get(['id', 'slug']);

        $roleCounts = DB::table('permission_role')
            ->select('permission_id', DB::raw('count(*) as aggregate'))
            ->groupBy('permission_id')
            ->pluck('aggregate', 'permission_id');

        $userCounts = DB::table('permission_user')
            ->where('type', 'grant')
            ->select('permission_id', DB::raw('count(*) as aggregate'))
            ->groupBy('permission_id')
            ->pluck('aggregate', 'permission_id');

        $deniedCounts = $this->deniedCounts();

        $report = [];

        foreach ($permissions as $permission) {
            $report[$permission->slug] = [
                'granted_roles' => (int) ($roleCounts[$permission->id] ?? 0),
                'granted_users' => (int) ($userCounts[$permission->id] ?? 0),
                'denied' => (int) ($deniedCounts[$permission->slug] ?? 0),
            ];
        }

        return $report;
    }

    /**
     * Count permission_denied audit events per slug within the window.
     *
     * @return array<string, int>
     */
    private function deniedCounts(): array
    {
        $window = (int) config('rbac.usage.window_days', 30);

        return AuditLog::query()
            ->where('action', AuditAction::PermissionDenied->value)
            ->where('created_at', '>=', now()->subDays($window))
            ->get(['metadata'])
            ->groupBy(fn (AuditLog $log) => $log->metadata['permission'] ?? '—')
            ->map->count()
            ->all();
    }
}
