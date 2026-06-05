<?php

declare(strict_types=1);

use App\Actions\Authorization\ResolveUserPermissions;
use App\Actions\Authorization\SyncRolePermissions;
use App\Enums\DirectPermissionType;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->tenant = makeTenant();
    $this->resolver = app(ResolveUserPermissions::class);
});

it('seeds a wildcard permission row for every module', function () {
    expect(Permission::query()->where('is_wildcard', true)->where('slug', 'deals.*')->exists())->toBeTrue();
    expect(Permission::query()->where('slug', 'companies.*')->exists())->toBeTrue();
});

it('expands a wildcard role permission into every concrete slug', function () {
    $role = Role::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Deal Master',
        'slug' => 'deal-master',
        'level' => 30,
    ]);
    $wildcard = Permission::query()->where('slug', 'deals.*')->firstOrFail();
    $role->permissions()->attach($wildcard->id);

    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $perms = $this->resolver->handle($user);

    expect($perms)
        ->toHaveKey('deals.view')
        ->toHaveKey('deals.create')
        ->toHaveKey('deals.update')
        ->toHaveKey('deals.delete')
        ->toHaveKey('deals.approve')
        ->toHaveKey('deals.export')
        ->not->toHaveKey('deals.*')
        ->not->toHaveKey('companies.view');
});

it('lets a deny override one slug inside a granted wildcard', function () {
    $role = Role::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Deal Master',
        'slug' => 'deal-master',
        'level' => 30,
    ]);
    $role->permissions()->attach(Permission::query()->where('slug', 'deals.*')->firstOrFail()->id);

    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $user->roles()->attach($role->id, ['assigned_at' => now()]);

    $deleteId = Permission::query()->where('slug', 'deals.delete')->firstOrFail()->id;
    $user->directPermissions()->attach($deleteId, ['type' => DirectPermissionType::Deny->value]);

    expect($this->resolver->handle($user))
        ->toHaveKey('deals.view')
        ->not->toHaveKey('deals.delete');
});

it('accepts a wildcard slug through SyncRolePermissions', function () {
    $admin = User::factory()->superAdmin()->create();
    $role = Role::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Custom',
        'slug' => 'custom',
        'level' => 10,
    ]);

    app(SyncRolePermissions::class)->handle($admin, $role, ['deals.*', 'companies.view']);

    expect($role->permissions()->pluck('slug'))->toContain('deals.*', 'companies.view');
});

it('rejects an unknown wildcard slug', function () {
    $admin = User::factory()->superAdmin()->create();
    $role = tenantRole($this->tenant, 'viewer');

    expect(fn () => app(SyncRolePermissions::class)->handle($admin, $role, ['nonsense.*']))
        ->toThrow(DomainException::class);
});

it('blocks granting a wildcard the actor does not fully hold', function () {
    $sales = makeUserWithRole($this->tenant, 'sales'); // no deals.delete/approve
    $role = Role::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Custom',
        'slug' => 'custom',
        'level' => 10,
    ]);

    expect(fn () => app(SyncRolePermissions::class)->handle($sales, $role, ['deals.*']))
        ->toThrow(DomainException::class);
});
