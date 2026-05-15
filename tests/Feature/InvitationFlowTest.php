<?php

declare(strict_types=1);

use App\Models\Invitation;
use App\Models\Permission;
use App\Models\User;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('allows tenant admin to invite a user with a role', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $sales = tenantRole($this->tenant, 'sales');

    $this->actingAs($admin)
        ->post(route('admin.users.invite', $this->tenant), [
            'email' => 'new@acme.test',
            'role_id' => $sales->id,
        ])
        ->assertRedirect();

    $this->assertDatabaseHas('invitations', [
        'tenant_id' => $this->tenant->id,
        'email' => 'new@acme.test',
        'role_id' => $sales->id,
    ]);
});

it('blocks role escalation: cannot invite a user with a higher role', function () {
    $manager = makeUserWithRole($this->tenant, 'manager');
    $invitePerm = Permission::query()->where('slug', 'users.invite')->firstOrFail();
    $manager->directPermissions()->attach($invitePerm->id, ['type' => 'grant']);

    $admin = tenantRole($this->tenant, 'tenant-admin');

    $this->actingAs($manager->fresh())
        ->post(route('admin.users.invite', $this->tenant), [
            'email' => 'new@acme.test',
            'role_id' => $admin->id,
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    $this->assertDatabaseMissing('invitations', ['email' => 'new@acme.test']);
});

it('accepts an invitation and creates a tenant user with the specified role', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $sales = tenantRole($this->tenant, 'sales');

    $invitation = Invitation::factory()->create([
        'tenant_id' => $this->tenant->id,
        'email' => 'invited@acme.test',
        'role_id' => $sales->id,
        'invited_by' => $admin->id,
    ]);

    $this->post(route('invitation.accept', $invitation->token), [
        'name' => 'Invited User',
        'password' => 'secret-pass',
        'password_confirmation' => 'secret-pass',
    ])->assertRedirect(route('tenant.dashboard', $this->tenant));

    $user = User::query()->where('email', 'invited@acme.test')->firstOrFail();
    expect($user->tenant_id)->toBe($this->tenant->id)
        ->and($user->roles->pluck('slug')->all())->toContain('sales');

    expect($invitation->fresh()->accepted_at)->not->toBeNull();
});

it('rejects an expired invitation', function () {
    $invitation = Invitation::factory()->expired()->create(['tenant_id' => $this->tenant->id]);

    $this->get(route('invitation.show', $invitation->token))->assertStatus(410);
});
