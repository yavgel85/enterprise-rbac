<?php

declare(strict_types=1);

use App\Authorization\TenantAuthorizer;
use App\Enums\Permission as PermissionEnum;
use App\Models\Deal;
use App\Models\Tenant;
use App\Models\User;

beforeEach(function () {
    $this->tenant = makeTenant();
    $this->authorizer = app(TenantAuthorizer::class);
});

it('allows super admins everywhere', function () {
    $super = User::factory()->superAdmin()->create();

    expect($this->authorizer->allows($super, PermissionEnum::DealsDelete)->allowed())->toBeTrue();
});

it('denies inactive users', function () {
    $user = makeUserWithRole($this->tenant, 'tenant-admin', ['is_active' => false]);

    $response = $this->authorizer->allows($user->fresh(), PermissionEnum::DealsView, $this->tenant);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toContain('inactive');
});

it('denies access for an inactive tenant', function () {
    $this->tenant->update(['is_active' => false]);
    $user = makeUserWithRole($this->tenant->fresh(), 'sales');

    $response = $this->authorizer->allows($user->fresh(), PermissionEnum::DealsView, $this->tenant->fresh());

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toContain('inactive');
});

it('denies cross-tenant resource access', function () {
    $otherTenant = Tenant::factory()->create();
    $user = makeUserWithRole($this->tenant, 'tenant-admin');

    $deal = Deal::factory()->create(['tenant_id' => $otherTenant->id]);

    $response = $this->authorizer->allows($user, PermissionEnum::DealsView, $this->tenant, $deal);

    expect($response->denied())->toBeTrue()
        ->and($response->message())->toContain('Cross-tenant');
});

it('denies users missing the required permission', function () {
    $viewer = makeUserWithRole($this->tenant, 'viewer');

    expect($this->authorizer->allows($viewer, PermissionEnum::DealsDelete, $this->tenant)->denied())->toBeTrue();
});

it('allows users with the required permission', function () {
    $sales = makeUserWithRole($this->tenant, 'sales');

    expect($this->authorizer->allows($sales, PermissionEnum::DealsView, $this->tenant)->allowed())->toBeTrue();
});
