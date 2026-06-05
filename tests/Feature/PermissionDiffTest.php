<?php

declare(strict_types=1);

use App\Models\Permission;
use App\Models\Role;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('flashes the added/removed permission diff after a sync', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $role = Role::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Custom',
        'slug' => 'custom',
        'level' => 10,
    ]);
    $role->permissions()->attach(Permission::query()->where('slug', 'deals.view')->firstOrFail()->id);

    $this->actingAs($admin)
        ->put("/t/{$this->tenant->slug}/admin/roles/{$role->id}/permissions", [
            'permissions' => ['deals.create', 'contacts.view'],
        ])
        ->assertRedirect()
        ->assertSessionHas('perm_diff', function (array $diff) {
            return in_array('deals.create', $diff['added'], true)
                && in_array('contacts.view', $diff['added'], true)
                && in_array('deals.view', $diff['removed'], true);
        });
});

it('shows the affected-user impact panel on the edit page', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $role = Role::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Custom',
        'slug' => 'custom',
        'level' => 10,
    ]);
    $member = makeUserWithRole($this->tenant, 'viewer');
    $member->roles()->syncWithoutDetaching([$role->id => ['assigned_at' => now()]]);

    $this->actingAs($admin)
        ->get("/t/{$this->tenant->slug}/admin/roles/{$role->id}/edit")
        ->assertOk()
        ->assertSee('user(s) affected')
        ->assertSee($member->email);
});
