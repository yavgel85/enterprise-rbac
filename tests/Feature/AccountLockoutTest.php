<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\User;

it('locks an account after five consecutive failed login attempts', function () {
    User::factory()->create([
        'email' => 'victim@x.test',
        'password' => 'correct-password',
    ]);

    for ($i = 0; $i < 5; $i++) {
        $this->post('/login', [
            'email' => 'victim@x.test',
            'password' => 'nope-'.$i,
        ]);
    }

    $user = User::query()->where('email', 'victim@x.test')->first();

    expect($user->failed_login_attempts)->toBe(5);
    expect($user->isLocked())->toBeTrue();

    expect(AuditLog::query()->where('action', 'account_locked')->exists())->toBeTrue();
});

it('blocks login while account is locked, even with correct credentials', function () {
    $user = User::factory()->create([
        'email' => 'locked@x.test',
        'password' => 'correct-password',
        'failed_login_attempts' => 5,
        'locked_until' => now()->addMinutes(15),
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('resets the failed attempts counter after a successful login', function () {
    User::factory()->create([
        'email' => 'recovered@x.test',
        'password' => 'correct-password',
        'failed_login_attempts' => 2,
    ]);

    $this->post('/login', [
        'email' => 'recovered@x.test',
        'password' => 'correct-password',
    ]);

    $fresh = User::query()->where('email', 'recovered@x.test')->first();

    expect($fresh->failed_login_attempts)->toBe(0);
    expect($fresh->locked_until)->toBeNull();
});

it('lets an admin unlock the account from the user page', function () {
    $tenant = makeTenant();
    $admin = makeUserWithRole($tenant, 'tenant-admin');
    $victim = makeUserWithRole($tenant, 'sales', [
        'failed_login_attempts' => 5,
        'locked_until' => now()->addMinutes(15),
    ]);

    $this->actingAs($admin)
        ->put("/t/{$tenant->slug}/admin/users/{$victim->id}/unlock")
        ->assertRedirect();

    $fresh = $victim->fresh();

    expect($fresh->failed_login_attempts)->toBe(0);
    expect($fresh->locked_until)->toBeNull();
    expect(AuditLog::query()->where('action', 'account_unlocked')->exists())->toBeTrue();
});
