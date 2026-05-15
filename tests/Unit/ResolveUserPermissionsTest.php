<?php

declare(strict_types=1);

use App\Actions\Authorization\ResolveUserPermissions;
use App\Enums\DirectPermissionType;
use App\Enums\Permission as PermissionEnum;
use App\Models\Permission;
use App\Models\User;

beforeEach(function () {
    $this->tenant = makeTenant();
    $this->resolver = app(ResolveUserPermissions::class);
});

it('returns every permission for super admin', function () {
    $super = User::factory()->superAdmin()->create();

    expect($this->resolver->handle($super))
        ->toHaveCount(count(PermissionEnum::cases()));
});

it('merges permissions across multiple roles', function () {
    $user = makeUserWithRole($this->tenant, 'sales');
    $user->roles()->syncWithoutDetaching([
        tenantRole($this->tenant, 'manager')->id => ['assigned_at' => now()],
    ]);

    $perms = $this->resolver->handle($user);

    expect($perms)
        ->toHaveKey('deals.view')
        ->toHaveKey('deals.approve')
        ->toHaveKey('audit.view');
});

it('honors direct grants on top of role permissions', function () {
    $user = makeUserWithRole($this->tenant, 'viewer');

    $permission = Permission::query()->where('slug', PermissionEnum::DealsUpdate->value)->firstOrFail();
    $user->directPermissions()->attach($permission->id, ['type' => DirectPermissionType::Grant->value]);

    expect($this->resolver->handle($user))
        ->toHaveKey('deals.update')
        ->and($user->hasPermission(PermissionEnum::DealsUpdate))->toBeTrue();
});

it('deny overrides role-granted permissions', function () {
    $user = makeUserWithRole($this->tenant, 'tenant-admin');

    $permission = Permission::query()->where('slug', PermissionEnum::DealsDelete->value)->firstOrFail();
    $user->directPermissions()->attach($permission->id, ['type' => DirectPermissionType::Deny->value]);

    expect($this->resolver->handle($user))
        ->not->toHaveKey('deals.delete')
        ->and($user->hasPermission(PermissionEnum::DealsDelete))->toBeFalse();
});

it('ignores expired direct permissions', function () {
    $user = makeUserWithRole($this->tenant, 'viewer');
    $permission = Permission::query()->where('slug', PermissionEnum::DealsUpdate->value)->firstOrFail();

    $user->directPermissions()->attach($permission->id, [
        'type' => DirectPermissionType::Grant->value,
        'expires_at' => now()->subDay(),
    ]);

    expect($this->resolver->handle($user))->not->toHaveKey('deals.update');
});

it('ignores expired role assignments', function () {
    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $user->roles()->attach(tenantRole($this->tenant, 'manager')->id, [
        'assigned_at' => now()->subWeek(),
        'expires_at' => now()->subDay(),
    ]);

    expect($this->resolver->handle($user))->toBe([]);
});
