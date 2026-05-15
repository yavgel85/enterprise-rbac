<?php

declare(strict_types=1);

use App\Models\Company;
use App\Models\User;

it('blocks users from accessing other tenants', function () {
    $acme = makeTenant('acme', 'Acme');
    $globex = makeTenant('globex', 'Globex');

    $user = makeUserWithRole($acme, 'tenant-admin');

    $this->actingAs($user)
        ->get(route('tenant.dashboard', $globex))
        ->assertForbidden();
});

it('shows only the current tenant data in CRM index pages', function () {
    $acme = makeTenant('acme', 'Acme');
    $globex = makeTenant('globex', 'Globex');

    Company::factory()->create(['tenant_id' => $acme->id, 'name' => 'Acme Inc']);
    Company::factory()->create(['tenant_id' => $globex->id, 'name' => 'Globex Inc']);

    $admin = makeUserWithRole($acme, 'tenant-admin');

    $this->actingAs($admin)
        ->get(route('crm.companies.index', $acme))
        ->assertSuccessful()
        ->assertSee('Acme Inc')
        ->assertDontSee('Globex Inc');
});

it('returns 404 for cross-tenant resources due to global scope', function () {
    $acme = makeTenant('acme', 'Acme');
    $globex = makeTenant('globex', 'Globex');

    $acmeAdmin = makeUserWithRole($acme, 'tenant-admin');
    $foreignCompany = Company::factory()->create(['tenant_id' => $globex->id]);

    $this->actingAs($acmeAdmin)
        ->get(route('crm.companies.show', [$acme, $foreignCompany]))
        ->assertNotFound();
});

it('lets a super admin view any tenant', function () {
    $acme = makeTenant('acme', 'Acme');
    $super = User::factory()->superAdmin()->create();

    $this->actingAs($super)
        ->get(route('tenant.dashboard', $acme))
        ->assertSuccessful();
});
