<?php

declare(strict_types=1);

use App\Actions\Authorization\CloneRole;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('clones a role with the same permissions, one level below', function () {
    $admin = User::factory()->superAdmin()->create();
    $manager = tenantRole($this->tenant, 'manager');

    $clone = app(CloneRole::class)->handle($admin, $this->tenant, $manager);

    expect($clone->is_system)->toBeFalse();
    expect($clone->level)->toBe($manager->level - 1);
    expect($clone->permissions()->pluck('slug')->sort()->values())
        ->toEqual($manager->permissions()->pluck('slug')->sort()->values());
});

it('copies the parent for inheritance continuity', function () {
    $admin = User::factory()->superAdmin()->create();
    $manager = tenantRole($this->tenant, 'manager');

    $clone = app(CloneRole::class)->handle($admin, $this->tenant, $manager);

    expect($clone->parent_id)->toBe($manager->parent_id);
});

it('prevents a non-super-admin from cloning a role at or above their level', function () {
    $manager = makeUserWithRole($this->tenant, 'manager'); // level 70
    $admin = tenantRole($this->tenant, 'tenant-admin');    // level 90 -> clone level 89

    expect(fn () => app(CloneRole::class)->handle($manager, $this->tenant, $admin))
        ->toThrow(DomainException::class);
});

it('clones from the UI and redirects to the new role editor', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $sales = tenantRole($this->tenant, 'sales');

    $this->actingAs($admin)
        ->post("/t/{$this->tenant->slug}/admin/roles/{$sales->id}/clone", [])
        ->assertRedirect();

    expect(Role::query()->where('tenant_id', $this->tenant->id)->where('slug', 'sales-representative-copy')->exists())
        ->toBeTrue();
});

it('rejects a duplicate slug', function () {
    $admin = User::factory()->superAdmin()->create();
    $sales = tenantRole($this->tenant, 'sales');

    expect(fn () => app(CloneRole::class)->handle($admin, $this->tenant, $sales, ['slug' => 'manager']))
        ->toThrow(DomainException::class);
});
