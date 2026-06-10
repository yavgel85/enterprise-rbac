<?php

declare(strict_types=1);

use App\Authorization\AbacGate;
use App\Authorization\TenantAuthorizer;
use App\Enums\Permission as PermissionEnum;
use App\Models\Deal;
use App\Models\Permission;
use App\Models\PermissionCondition;

beforeEach(function () {
    $this->tenant = makeTenant();
});

function addCondition(int $tenantId, string $slug, array $conditions, ?int $roleId = null): void
{
    PermissionCondition::create([
        'tenant_id' => $tenantId,
        'permission_id' => Permission::where('slug', $slug)->value('id'),
        'role_id' => $roleId,
        'conditions' => $conditions,
    ]);

    AbacGate::flushCache();
}

it('denies a granted permission when its ABAC condition fails', function () {
    $manager = makeUserWithRole($this->tenant, 'manager');
    $ownDeal = Deal::factory()->create(['tenant_id' => $this->tenant->id, 'owner_id' => $manager->id]);
    $otherDeal = Deal::factory()->create(['tenant_id' => $this->tenant->id, 'owner_id' => null]);

    addCondition($this->tenant->id, 'deals.approve', [
        'attr' => 'deal.owner_id', 'op' => '=', 'value' => '$user.id',
    ]);

    $auth = app(TenantAuthorizer::class);

    expect($auth->allows($manager, PermissionEnum::DealsApprove, $this->tenant, $ownDeal)->allowed())->toBeTrue();
    expect($auth->allows($manager, PermissionEnum::DealsApprove, $this->tenant, $otherDeal)->allowed())->toBeFalse();
});

it('leaves permissions without conditions unaffected', function () {
    $manager = makeUserWithRole($this->tenant, 'manager');
    $deal = Deal::factory()->create(['tenant_id' => $this->tenant->id, 'owner_id' => null]);

    expect(app(TenantAuthorizer::class)->allows($manager, PermissionEnum::DealsApprove, $this->tenant, $deal)->allowed())
        ->toBeTrue();
});

it('only applies role-scoped conditions to users in that role', function () {
    $manager = makeUserWithRole($this->tenant, 'manager');
    $deal = Deal::factory()->create(['tenant_id' => $this->tenant->id, 'owner_id' => null]);

    // Condition scoped to the sales role only — should not affect the manager.
    addCondition($this->tenant->id, 'deals.approve', [
        'attr' => 'deal.owner_id', 'op' => '=', 'value' => '$user.id',
    ], roleId: tenantRole($this->tenant, 'sales')->id);

    expect(app(TenantAuthorizer::class)->allows($manager, PermissionEnum::DealsApprove, $this->tenant, $deal)->allowed())
        ->toBeTrue();
});

it('supports all/any groups in the DSL', function () {
    $manager = makeUserWithRole($this->tenant, 'manager');
    $deal = Deal::factory()->create([
        'tenant_id' => $this->tenant->id,
        'owner_id' => $manager->id,
        'amount' => 5000,
    ]);

    addCondition($this->tenant->id, 'deals.approve', [
        'all' => [
            ['attr' => 'deal.owner_id', 'op' => '=', 'value' => '$user.id'],
            ['attr' => 'deal.amount', 'op' => '<', 'value' => 10000],
        ],
    ]);

    expect(app(TenantAuthorizer::class)->allows($manager, PermissionEnum::DealsApprove, $this->tenant, $deal)->allowed())
        ->toBeTrue();

    $deal->update(['amount' => 50000]);

    expect(app(TenantAuthorizer::class)->allows($manager, PermissionEnum::DealsApprove, $this->tenant, $deal->fresh())->allowed())
        ->toBeFalse();
});

it('lets a tenant-admin manage conditions from the UI', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $permissionId = Permission::where('slug', 'deals.delete')->value('id');

    $this->actingAs($admin)
        ->post("/t/{$this->tenant->slug}/admin/permission-conditions", [
            'permission_id' => $permissionId,
            'conditions' => json_encode(['attr' => 'deal.status', 'op' => '!=', 'value' => 'closed']),
            'description' => 'No deleting closed deals',
        ])
        ->assertRedirect();

    expect(PermissionCondition::where('tenant_id', $this->tenant->id)->count())->toBe(1);
});

it('forbids non-admins from managing conditions', function () {
    $sales = makeUserWithRole($this->tenant, 'sales');

    $this->actingAs($sales)
        ->get("/t/{$this->tenant->slug}/admin/permission-conditions")
        ->assertForbidden();
});

it('rejects invalid condition JSON from the UI', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $permissionId = Permission::where('slug', 'deals.delete')->value('id');

    $this->actingAs($admin)
        ->post("/t/{$this->tenant->slug}/admin/permission-conditions", [
            'permission_id' => $permissionId,
            'conditions' => 'not-json',
        ])
        ->assertSessionHasErrors('conditions');

    expect(PermissionCondition::where('tenant_id', $this->tenant->id)->count())->toBe(0);
});
