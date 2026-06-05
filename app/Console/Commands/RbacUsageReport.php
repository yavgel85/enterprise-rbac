<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Authorization\PermissionUsageReport;
use Illuminate\Console\Command;

class RbacUsageReport extends Command
{
    protected $signature = 'rbac:usage {--unused : Only list permissions never granted to any role or user}';

    protected $description = 'Report permission usage (granted roles/users and denials) to spot dead permissions.';

    public function handle(PermissionUsageReport $report): int
    {
        $window = (int) config('rbac.usage.window_days', 30);
        $stats = $report->handle(fresh: true);

        $onlyUnused = (bool) $this->option('unused');

        $rows = [];
        foreach ($stats as $slug => $row) {
            $isUnused = $row['granted_roles'] === 0 && $row['granted_users'] === 0;

            if ($onlyUnused && ! $isUnused) {
                continue;
            }

            $rows[] = [
                $slug,
                $row['granted_roles'],
                $row['granted_users'],
                $row['denied'],
                $isUnused ? 'UNUSED' : '',
            ];
        }

        $this->info("Permission usage (denials over last {$window} days):");
        $this->table(
            ['Permission', 'Roles', 'Direct users', 'Denied', 'Flag'],
            $rows,
        );

        $unusedCount = collect($stats)->filter(
            fn (array $r) => $r['granted_roles'] === 0 && $r['granted_users'] === 0
        )->count();

        $this->line('');
        $this->line('Total permissions: '.count($stats).' · never granted: '.$unusedCount);

        return self::SUCCESS;
    }
}
