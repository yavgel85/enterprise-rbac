<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

it('lets a tenant-admin set a sales users password', function () {
    $tenant = makeTenant();
    $admin = makeUserWithRole($tenant, 'tenant-admin');
    $sales = makeUserWithRole($tenant, 'sales', ['password' => 'old']);

    DB::table('sessions')->insert([
        'id' => 'session-of-sales',
        'user_id' => $sales->id,
        'ip_address' => null,
        'user_agent' => null,
        'payload' => '',
        'last_activity' => now()->getTimestamp(),
    ]);

    $this->actingAs($admin)
        ->put("/t/{$tenant->slug}/admin/users/{$sales->id}/password", [
            'password' => 'fresh-strong-pw',
            'password_confirmation' => 'fresh-strong-pw',
        ])
        ->assertRedirect();

    expect(Hash::check('fresh-strong-pw', $sales->fresh()->password))->toBeTrue();
    expect(DB::table('sessions')->where('id', 'session-of-sales')->exists())->toBeFalse();
    expect(AuditLog::query()->where('action', 'password_changed_by_admin')->exists())->toBeTrue();
});

it('forbids a tenant-admin from changing another tenant-admins password', function () {
    $tenant = makeTenant();
    $admin = makeUserWithRole($tenant, 'tenant-admin');
    $peer = makeUserWithRole($tenant, 'tenant-admin', ['password' => 'old']);

    $this->actingAs($admin)
        ->put("/t/{$tenant->slug}/admin/users/{$peer->id}/password", [
            'password' => 'new-strong-pw',
            'password_confirmation' => 'new-strong-pw',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Hash::check('old', $peer->fresh()->password))->toBeTrue();
});

it('forbids a tenant-admin from changing a super-admins password', function () {
    $tenant = makeTenant();
    $admin = makeUserWithRole($tenant, 'tenant-admin');
    $super = User::factory()->superAdmin()->create([
        'tenant_id' => $tenant->id,
        'password' => 'old',
    ]);

    $this->actingAs($admin)
        ->put("/t/{$tenant->slug}/admin/users/{$super->id}/password", [
            'password' => 'new-strong-pw',
            'password_confirmation' => 'new-strong-pw',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Hash::check('old', $super->fresh()->password))->toBeTrue();
});

it('lets a super-admin change a tenant-admins password', function () {
    $tenant = makeTenant();
    $super = User::factory()->superAdmin()->create();
    $admin = makeUserWithRole($tenant, 'tenant-admin', ['password' => 'old']);

    $this->actingAs($super)
        ->put("/t/{$tenant->slug}/admin/users/{$admin->id}/password", [
            'password' => 'super-set-pw',
            'password_confirmation' => 'super-set-pw',
        ])
        ->assertRedirect();

    expect(Hash::check('super-set-pw', $admin->fresh()->password))->toBeTrue();
    expect(AuditLog::query()->where('action', 'password_changed_by_admin')->exists())->toBeTrue();
});

it('refuses to let an admin reset their own password through the admin form', function () {
    $tenant = makeTenant();
    $admin = makeUserWithRole($tenant, 'tenant-admin', ['password' => 'old']);

    $this->actingAs($admin)
        ->put("/t/{$tenant->slug}/admin/users/{$admin->id}/password", [
            'password' => 'self-pw',
            'password_confirmation' => 'self-pw',
        ])
        ->assertForbidden();
});

it('rejects a manager without users.set-password permission', function () {
    $tenant = makeTenant();
    $manager = makeUserWithRole($tenant, 'manager');
    $target = makeUserWithRole($tenant, 'sales', ['password' => 'old']);

    $this->actingAs($manager)
        ->put("/t/{$tenant->slug}/admin/users/{$target->id}/password", [
            'password' => 'new-pw',
            'password_confirmation' => 'new-pw',
        ])
        ->assertForbidden();

    expect(Hash::check('old', $target->fresh()->password))->toBeTrue();
});
