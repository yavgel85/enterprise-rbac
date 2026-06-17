<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\Authorization\ForgetUserPermissionsCache;
use App\Actions\Authorization\ResolveUserPermissions;
use App\Models\User;
use Illuminate\Console\Command;

class WarmPermissionCache extends Command
{
    protected $signature = 'rbac:warm-cache {--tenant= : Restrict warming to a single tenant id}';

    protected $description = 'Pre-resolve and cache permissions for active users (run after deploy or cache flush).';

    public function handle(ResolveUserPermissions $resolver, ForgetUserPermissionsCache $forget): int
    {
        $count = 0;

        User::query()
            ->where('is_active', true)
            ->where('is_super_admin', false)
            ->when($this->option('tenant'), fn ($query, $tenant) => $query->where('tenant_id', $tenant))
            ->lazyById()
            ->each(function (User $user) use ($resolver, $forget, &$count): void {
                $forget->forUser($user);
                $resolver->handle($user);
                $count++;
            });

        $this->info("Warmed permission cache for {$count} user(s).");

        return self::SUCCESS;
    }
}
