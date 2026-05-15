<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;

it('allows super admin to view tenants page', function () {
    seedRbacCatalog();
    $super = User::factory()->superAdmin()->create();

    $this->actingAs($super)
        ->get(route('super-admin.tenants.index'))
        ->assertSuccessful();
});

it('blocks tenant admin from super admin pages', function () {
    $tenant = makeTenant();
    $admin = makeUserWithRole($tenant, 'tenant-admin');

    $this->actingAs($admin)
        ->get(route('super-admin.tenants.index'))
        ->assertForbidden();
});

it('lets super admin create and bootstrap a new tenant', function () {
    seedRbacCatalog();
    $super = User::factory()->superAdmin()->create();

    $this->actingAs($super)
        ->post(route('super-admin.tenants.store'), [
            'name' => 'New Co',
            'slug' => 'newco',
        ])->assertRedirect();

    $tenant = Tenant::query()->where('slug', 'newco')->firstOrFail();

    expect($tenant->roles()->count())->toBe(5)
        ->and($tenant->departments()->count())->toBe(1);
});
