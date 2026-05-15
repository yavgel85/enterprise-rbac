<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Models\Feature;
use App\Models\Permission;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('blocks audit export when the feature is disabled', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $permission = Permission::query()->where('slug', PermissionEnum::AuditExport->value)->firstOrFail();
    $admin->directPermissions()->attach($permission->id, ['type' => 'grant']);

    $this->actingAs($admin)
        ->post(route('admin.audit.export', $this->tenant))
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('allows audit export when the feature is enabled', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    $feature = Feature::query()->where('slug', 'audit_export')->firstOrFail();
    $this->tenant->features()->attach($feature->id, ['is_enabled' => true]);

    $permission = Permission::query()->where('slug', PermissionEnum::AuditExport->value)->firstOrFail();
    $admin->directPermissions()->attach($permission->id, ['type' => 'grant']);

    $response = $this->actingAs($admin)
        ->post(route('admin.audit.export', $this->tenant));

    $response->assertSuccessful();
});
