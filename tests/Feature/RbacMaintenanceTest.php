<?php

declare(strict_types=1);

use App\Actions\Authorization\ResolveUserPermissions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('warms the permission cache for active users', function () {
    $user = makeUserWithRole($this->tenant, 'sales');
    Cache::forget(ResolveUserPermissions::cacheKey($user));

    $this->artisan('rbac:warm-cache')->assertSuccessful();

    expect(Cache::has(ResolveUserPermissions::cacheKey($user)))->toBeTrue();
});

it('skips inactive and super-admin users when warming', function () {
    $inactive = makeUserWithRole($this->tenant, 'sales', ['is_active' => false]);
    Cache::forget(ResolveUserPermissions::cacheKey($inactive));

    $this->artisan('rbac:warm-cache')->assertSuccessful();

    expect(Cache::has(ResolveUserPermissions::cacheKey($inactive)))->toBeFalse();
});

it('prunes expired role grants and keeps active ones', function () {
    $user = makeUserWithRole($this->tenant, 'sales');
    $managerRoleId = tenantRole($this->tenant, 'manager')->id;
    $viewerRoleId = tenantRole($this->tenant, 'viewer')->id;

    DB::table('role_user')->insert([
        'user_id' => $user->id,
        'role_id' => $managerRoleId,
        'assigned_at' => now()->subDays(10),
        'expires_at' => now()->subDay(),
    ]);
    DB::table('role_user')->insert([
        'user_id' => $user->id,
        'role_id' => $viewerRoleId,
        'assigned_at' => now(),
        'expires_at' => now()->addDay(),
    ]);

    $this->artisan('rbac:prune-expired')->assertSuccessful();

    expect(DB::table('role_user')->where('user_id', $user->id)->where('role_id', $managerRoleId)->exists())->toBeFalse()
        ->and(DB::table('role_user')->where('user_id', $user->id)->where('role_id', $viewerRoleId)->exists())->toBeTrue();
});

it('does not delete anything on a dry run', function () {
    $user = makeUserWithRole($this->tenant, 'sales');
    $managerRoleId = tenantRole($this->tenant, 'manager')->id;

    DB::table('role_user')->insert([
        'user_id' => $user->id,
        'role_id' => $managerRoleId,
        'assigned_at' => now()->subDays(10),
        'expires_at' => now()->subDay(),
    ]);

    $this->artisan('rbac:prune-expired --dry-run')->assertSuccessful();

    expect(DB::table('role_user')->where('user_id', $user->id)->where('role_id', $managerRoleId)->exists())->toBeTrue();
});
