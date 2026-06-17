<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Authorization\ForgetUserPermissionsCache;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class PruneExpiredGrants extends Command
{
    protected $signature = 'rbac:prune-expired {--dry-run : Report what would be pruned without deleting}';

    protected $description = 'Delete expired role/permission grants from pivot tables and flush affected user caches.';

    public function handle(ForgetUserPermissionsCache $forget): int
    {
        $now = now();
        $dryRun = (bool) $this->option('dry-run');

        $expiredRoles = DB::table('role_user')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now);

        $expiredPerms = DB::table('permission_user')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<', $now);

        $affectedUserIds = (clone $expiredRoles)->pluck('user_id')
            ->merge((clone $expiredPerms)->pluck('user_id'))
            ->unique()
            ->values();

        $roleCount = (clone $expiredRoles)->count();
        $permCount = (clone $expiredPerms)->count();

        if ($dryRun) {
            $this->info("[dry-run] Would prune {$roleCount} role grant(s) and {$permCount} permission grant(s) across {$affectedUserIds->count()} user(s).");

            return self::SUCCESS;
        }

        $expiredRoles->delete();
        $expiredPerms->delete();

        User::query()
            ->whereIn('id', $affectedUserIds)
            ->lazyById()
            ->each(fn (User $user) => $forget->forUser($user));

        $this->info("Pruned {$roleCount} role grant(s) and {$permCount} permission grant(s); flushed cache for {$affectedUserIds->count()} user(s).");

        return self::SUCCESS;
    }
}
