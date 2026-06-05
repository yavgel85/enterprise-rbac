<?php

declare(strict_types=1);

use App\Actions\Authorization\ApplyPermissionGroupToRole;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('seeds global permission bundles', function () {
    expect(PermissionGroup::query()->whereNull('tenant_id')->where('slug', 'crm-read-only')->exists())->toBeTrue();
    expect(PermissionGroup::query()->where('slug', 'crm-full')->first()->permissions()->count())->toBeGreaterThan(5);
});

it('merges a bundle into a role additively', function () {
    $admin = User::factory()->superAdmin()->create();
    $role = Role::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Custom',
        'slug' => 'custom',
        'level' => 10,
    ]);
    $role->permissions()->attach(
        Permission::query()->where('slug', 'audit.view')->firstOrFail()->id
    );

    $group = PermissionGroup::query()->where('slug', 'crm-read-only')->firstOrFail();
    app(ApplyPermissionGroupToRole::class)->handle($admin, $role, $group);

    $slugs = $role->fresh()->permissions->pluck('slug');

    expect($slugs)
        ->toContain('audit.view')      // pre-existing kept
        ->toContain('companies.view')  // from bundle
        ->toContain('deals.view');
});

it('applies a bundle from the UI', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $role = Role::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Custom',
        'slug' => 'custom',
        'level' => 10,
    ]);
    $group = PermissionGroup::query()->where('slug', 'crm-read-only')->firstOrFail();

    $this->actingAs($admin)
        ->post("/t/{$this->tenant->slug}/admin/roles/{$role->id}/apply-group", [
            'permission_group_id' => $group->id,
        ])
        ->assertRedirect();

    expect($role->fresh()->permissions->pluck('slug'))->toContain('contacts.view');
});

it('blocks a non-super-admin from applying a bundle with permissions they lack', function () {
    $sales = makeUserWithRole($this->tenant, 'sales');
    $role = Role::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'Custom',
        'slug' => 'custom',
        'level' => 10,
    ]);
    $group = PermissionGroup::query()->where('slug', 'user-administration')->firstOrFail();

    expect(fn () => app(ApplyPermissionGroupToRole::class)->handle($sales, $role, $group))
        ->toThrow(DomainException::class);
});
