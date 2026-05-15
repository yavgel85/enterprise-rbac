<?php

declare(strict_types=1);

use App\Models\Tenant;
use App\Models\User;

it('redirects guests to login from root', function () {
    $this->get('/')->assertRedirect(route('login'));
});

it('shows the login page', function () {
    $this->get('/login')->assertSuccessful()->assertSee('Sign in');
});

it('logs a tenant user in and redirects to their tenant dashboard', function () {
    $tenant = Tenant::factory()->create(['slug' => 'acme']);
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'user@acme.test',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => 'user@acme.test',
        'password' => 'password',
    ])->assertRedirect(route('tenant.dashboard', $tenant));

    $this->assertAuthenticatedAs($user);
});

it('logs a super admin in and redirects to platform tenants list', function () {
    $super = User::factory()->superAdmin()->create([
        'email' => 'super@x.test',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => 'super@x.test',
        'password' => 'password',
    ])->assertRedirect(route('super-admin.tenants.index'));
});

it('rejects invalid credentials', function () {
    User::factory()->create([
        'email' => 'real@x.test',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => 'real@x.test',
        'password' => 'wrong',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
