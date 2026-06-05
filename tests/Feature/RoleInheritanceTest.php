<?php

declare(strict_types=1);

use App\Actions\Authorization\ResolveUserPermissions;
use App\Actions\Authorization\SetRoleParent;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->tenant = makeTenant();
    $this->resolver = app(ResolveUserPermissions::class);
});

it('seeds the system role inheritance chain on bootstrap', function () {
    $manager = tenantRole($this->tenant, 'manager');
    $sales = tenantRole($this->tenant, 'sales');
    $viewer = tenantRole($this->tenant, 'viewer');
    $admin = tenantRole($this->tenant, 'tenant-admin');

    expect($admin->parent_id)->toBe($manager->id);
    expect($manager->parent_id)->toBe($sales->id);
    expect($sales->parent_id)->toBe($viewer->id);
    expect(tenantRole($this->tenant, 'auditor')->parent_id)->toBeNull();
});

it('grants a child role the permissions of its parent', function () {
    // Custom child with no own permissions, inheriting from sales.
    $sales = tenantRole($this->tenant, 'sales');
    $child = Role::create([
        'tenant_id' => $this->tenant->id,
        'parent_id' => $sales->id,
        'name' => 'Junior Sales',
        'slug' => 'junior-sales',
        'level' => 20,
    ]);

    $user = User::factory()->create(['tenant_id' => $this->tenant->id]);
    $user->roles()->attach($child->id, ['assigned_at' => now()]);

    $perms = $this->resolver->handle($user);

    expect($perms)
        ->toHaveKey('deals.view')
        ->toHaveKey('deals.create')
        ->toHaveKey('companies.view');
});

it('walks the full ancestor chain (grandparent permissions included)', function () {
    // viewer <- sales <- manager : a manager user must also see viewer/sales perms
    // and manager-only perms, all via the seeded chain.
    $user = makeUserWithRole($this->tenant, 'manager');

    $perms = $this->resolver->handle($user);

    expect($perms)
        ->toHaveKey('deals.approve')   // manager's own
        ->toHaveKey('deals.create')    // from sales
        ->toHaveKey('deals.view');     // from viewer
});

it('rejects setting a role as its own parent', function () {
    $role = tenantRole($this->tenant, 'sales');
    $actor = User::factory()->superAdmin()->create();

    expect(fn () => app(SetRoleParent::class)->handle($actor, $role, $role->id))
        ->toThrow(DomainException::class);
});

it('rejects a parent that would create a cycle', function () {
    // sales already inherits (transitively) below manager; making sales the
    // parent of manager closes a loop manager -> sales -> ... -> manager.
    $manager = tenantRole($this->tenant, 'manager');
    $sales = tenantRole($this->tenant, 'sales');
    $actor = User::factory()->superAdmin()->create();

    // manager.parent currently = sales. Try to set sales.parent = manager → cycle.
    expect(fn () => app(SetRoleParent::class)->handle($actor, $sales, $manager->id))
        ->toThrow(DomainException::class);
});

it('rejects a parent from another tenant', function () {
    $other = makeTenant('globex', 'Globex');
    $role = tenantRole($this->tenant, 'sales');
    $foreignParent = tenantRole($other, 'manager');
    $actor = User::factory()->superAdmin()->create();

    expect(fn () => app(SetRoleParent::class)->handle($actor, $role, $foreignParent->id))
        ->toThrow(DomainException::class);
});

it('clears inheritance when parent is set to null', function () {
    $manager = tenantRole($this->tenant, 'manager');
    $actor = User::factory()->superAdmin()->create();

    app(SetRoleParent::class)->handle($actor, $manager, null);

    expect($manager->fresh()->parent_id)->toBeNull();

    // Without a parent, a manager user keeps only manager's explicit perms,
    // and loses the inherited viewer-only chain extras (none unique here),
    // but still has manager perms.
    $user = makeUserWithRole($this->tenant, 'manager');
    expect($this->resolver->handle($user))->toHaveKey('deals.approve');
});

it('lets an admin set role inheritance from the UI', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $viewer = tenantRole($this->tenant, 'viewer');
    $child = Role::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Custom',
        'slug' => 'custom',
        'level' => 5,
    ]);

    $this->actingAs($admin)
        ->put("/t/{$this->tenant->slug}/admin/roles/{$child->id}/parent", [
            'parent_id' => $viewer->id,
        ])
        ->assertRedirect();

    expect($child->fresh()->parent_id)->toBe($viewer->id);
});
