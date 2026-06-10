<?php

declare(strict_types=1);

use App\Authorization\TenantAuthorizer;
use App\Enums\Permission as PermissionEnum;
use App\Models\Deal;
use App\Models\Permission;
use App\Models\ResourcePermission;

beforeEach(function () {
    $this->tenant = makeTenant();
});

function grantInstance(int $tenantId, int $userId, string $slug, Deal $deal, ?DateTimeInterface $expiresAt = null): void
{
    ResourcePermission::create([
        'tenant_id' => $tenantId,
        'user_id' => $userId,
        'permission_id' => Permission::where('slug', $slug)->value('id'),
        'resource_type' => $deal->getMorphClass(),
        'resource_id' => $deal->getKey(),
        'expires_at' => $expiresAt,
    ]);
}

it('grants access to a single resource instance without a role permission', function () {
    $viewer = makeUserWithRole($this->tenant, 'viewer'); // no deals.update
    $deal = Deal::factory()->create(['tenant_id' => $this->tenant->id]);
    $otherDeal = Deal::factory()->create(['tenant_id' => $this->tenant->id]);

    $auth = app(TenantAuthorizer::class);
    expect($auth->allows($viewer, PermissionEnum::DealsUpdate, $this->tenant, $deal)->allowed())->toBeFalse();

    grantInstance($this->tenant->id, $viewer->id, 'deals.update', $deal);

    expect($auth->allows($viewer, PermissionEnum::DealsUpdate, $this->tenant, $deal)->allowed())->toBeTrue();
    expect($auth->allows($viewer, PermissionEnum::DealsUpdate, $this->tenant, $otherDeal)->allowed())->toBeFalse();
});

it('ignores expired instance grants', function () {
    $viewer = makeUserWithRole($this->tenant, 'viewer');
    $deal = Deal::factory()->create(['tenant_id' => $this->tenant->id]);

    grantInstance($this->tenant->id, $viewer->id, 'deals.update', $deal, now()->subHour());

    expect(app(TenantAuthorizer::class)->allows($viewer, PermissionEnum::DealsUpdate, $this->tenant, $deal)->allowed())
        ->toBeFalse();
});

it('does not override cross-tenant or inactive checks', function () {
    $other = makeTenant('globex', 'Globex');
    $viewer = makeUserWithRole($this->tenant, 'viewer');
    $foreignDeal = Deal::factory()->create(['tenant_id' => $other->id]);

    grantInstance($this->tenant->id, $viewer->id, 'deals.update', $foreignDeal);

    // The resource belongs to another tenant — cross-tenant guard still denies.
    expect(app(TenantAuthorizer::class)->allows($viewer, PermissionEnum::DealsUpdate, $this->tenant, $foreignDeal)->allowed())
        ->toBeFalse();
});

it('lets a permissions.assign holder grant and revoke instance permissions from the UI', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $viewer = makeUserWithRole($this->tenant, 'viewer');
    $deal = Deal::factory()->create(['tenant_id' => $this->tenant->id]);
    $permissionId = Permission::where('slug', 'deals.update')->value('id');

    $this->actingAs($admin)
        ->post("/t/{$this->tenant->slug}/crm/deals/{$deal->id}/instance-permissions", [
            'user_id' => $viewer->id,
            'permission_id' => $permissionId,
        ])
        ->assertRedirect();

    $grant = ResourcePermission::where('user_id', $viewer->id)->firstOrFail();

    $this->actingAs($admin)
        ->delete("/t/{$this->tenant->slug}/crm/deals/{$deal->id}/instance-permissions/{$grant->id}")
        ->assertRedirect();

    expect(ResourcePermission::count())->toBe(0);
});

it('forbids non-assigners from granting instance permissions', function () {
    $sales = makeUserWithRole($this->tenant, 'sales');
    $viewer = makeUserWithRole($this->tenant, 'viewer');
    $deal = Deal::factory()->create(['tenant_id' => $this->tenant->id]);
    $permissionId = Permission::where('slug', 'deals.update')->value('id');

    $this->actingAs($sales)
        ->post("/t/{$this->tenant->slug}/crm/deals/{$deal->id}/instance-permissions", [
            'user_id' => $viewer->id,
            'permission_id' => $permissionId,
        ])
        ->assertForbidden();
});
