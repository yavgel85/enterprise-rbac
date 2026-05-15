<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Cache\Repository as Cache;

final readonly class ForgetUserPermissionsCache
{
    public function __construct(private Cache $cache) {}

    public function forUser(User $user): void
    {
        $this->cache->forget(ResolveUserPermissions::cacheKey($user));
    }

    public function forRole(Role $role): void
    {
        $role->users()
            ->select(['users.id', 'users.tenant_id'])
            ->lazyById()
            ->each(function (User $user) {
                $this->cache->forget(ResolveUserPermissions::cacheKey($user));
            });
    }
}
