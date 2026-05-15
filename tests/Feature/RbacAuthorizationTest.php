<?php

declare(strict_types=1);

use App\Models\Company;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('allows sales role to create a company', function () {
    $user = makeUserWithRole($this->tenant, 'sales');

    $this->actingAs($user)
        ->post(route('crm.companies.store', $this->tenant), [
            'name' => 'New Co',
            'status' => 'active',
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('companies', [
        'tenant_id' => $this->tenant->id,
        'name' => 'New Co',
    ]);
});

it('forbids viewer role from creating a company', function () {
    $viewer = makeUserWithRole($this->tenant, 'viewer');

    $this->actingAs($viewer)
        ->post(route('crm.companies.store', $this->tenant), [
            'name' => 'Forbidden Co',
            'status' => 'active',
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('companies', ['name' => 'Forbidden Co']);
});

it('forbids sales role from deleting a company', function () {
    $sales = makeUserWithRole($this->tenant, 'sales');
    $company = Company::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($sales)
        ->delete(route('crm.companies.destroy', [$this->tenant, $company]))
        ->assertForbidden();

    $this->assertDatabaseHas('companies', ['id' => $company->id, 'deleted_at' => null]);
});

it('allows tenant admin to delete a company', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $company = Company::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($admin)
        ->delete(route('crm.companies.destroy', [$this->tenant, $company]))
        ->assertRedirect();

    $this->assertSoftDeleted($company);
});

it('forbids inactive user', function () {
    $user = makeUserWithRole($this->tenant, 'tenant-admin', ['is_active' => false]);

    $this->actingAs($user->fresh())
        ->get(route('crm.companies.index', $this->tenant))
        ->assertForbidden();
});
