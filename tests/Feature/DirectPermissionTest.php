<?php

declare(strict_types=1);

use App\Enums\DirectPermissionType;
use App\Enums\Permission as PermissionEnum;
use App\Models\Deal;
use App\Models\Permission;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('grants a viewer extra permissions via a direct grant', function () {
    $viewer = makeUserWithRole($this->tenant, 'viewer');
    $permission = Permission::query()->where('slug', PermissionEnum::DealsCreate->value)->firstOrFail();

    $viewer->directPermissions()->attach($permission->id, [
        'type' => DirectPermissionType::Grant->value,
    ]);

    $this->actingAs($viewer)
        ->post(route('crm.deals.store', $this->tenant), [
            'title' => 'Granted deal',
            'amount' => 1000,
            'currency' => 'USD',
            'stage' => 'lead',
            'status' => 'draft',
            'probability' => 10,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('deals', ['title' => 'Granted deal']);
});

it('uses a deny override to block an otherwise allowed action', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $deal = Deal::factory()->create(['tenant_id' => $this->tenant->id]);

    $permission = Permission::query()->where('slug', PermissionEnum::DealsDelete->value)->firstOrFail();
    $admin->directPermissions()->attach($permission->id, [
        'type' => DirectPermissionType::Deny->value,
    ]);

    $this->actingAs($admin->fresh())
        ->delete(route('crm.deals.destroy', [$this->tenant, $deal]))
        ->assertForbidden();
});
