<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

it('shows the profile page for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/profile')
        ->assertSuccessful()
        ->assertSee('My profile')
        ->assertSee($user->email);
});

it('requires authentication for the profile page', function () {
    $this->get('/profile')->assertRedirect(route('login'));
});

it('lets a user change their own password with the correct current password', function () {
    $user = User::factory()->create([
        'password' => 'old-password',
        'failed_login_attempts' => 4,
    ]);

    $this->actingAs($user)
        ->put('/profile/password', [
            'current_password' => 'old-password',
            'password' => 'brand-new-pw',
            'password_confirmation' => 'brand-new-pw',
        ])
        ->assertRedirect();

    $fresh = $user->fresh();
    expect(Hash::check('brand-new-pw', $fresh->password))->toBeTrue();
    expect($fresh->failed_login_attempts)->toBe(0);
    expect(AuditLog::query()->where('action', 'password_changed_by_self')->exists())->toBeTrue();
});

it('rejects password change when the current password is wrong', function () {
    $user = User::factory()->create(['password' => 'old-password']);

    $this->actingAs($user)
        ->put('/profile/password', [
            'current_password' => 'wrong-current',
            'password' => 'brand-new-pw',
            'password_confirmation' => 'brand-new-pw',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('old-password', $user->fresh()->password))->toBeTrue();
});

it('lists the current users persisted sessions on the profile page', function () {
    $user = User::factory()->create();

    DB::table('sessions')->insert([
        'id' => 'fake-other-session',
        'user_id' => $user->id,
        'ip_address' => '203.0.113.42',
        'user_agent' => 'CursorBrowser/1.0',
        'payload' => '',
        'last_activity' => now()->subMinutes(5)->getTimestamp(),
    ]);

    $this->actingAs($user)
        ->get('/profile')
        ->assertSee('CursorBrowser/1.0');
});

it('terminates a specific session belonging to the current user', function () {
    $user = User::factory()->create();
    DB::table('sessions')->insert([
        'id' => 'kill-me',
        'user_id' => $user->id,
        'ip_address' => '203.0.113.42',
        'user_agent' => 'CursorBrowser/1.0',
        'payload' => '',
        'last_activity' => now()->getTimestamp(),
    ]);

    $this->actingAs($user)
        ->delete('/profile/sessions/kill-me')
        ->assertRedirect();

    expect(DB::table('sessions')->where('id', 'kill-me')->exists())->toBeFalse();
    expect(AuditLog::query()->where('action', 'session_terminated')->exists())->toBeTrue();
});

it('signs the user out of all other devices on demand', function () {
    $user = User::factory()->create(['password' => 'correct']);

    DB::table('sessions')->insert([
        ['id' => 'a', 'user_id' => $user->id, 'ip_address' => null, 'user_agent' => null, 'payload' => '', 'last_activity' => now()->getTimestamp()],
        ['id' => 'b', 'user_id' => $user->id, 'ip_address' => null, 'user_agent' => null, 'payload' => '', 'last_activity' => now()->getTimestamp()],
    ]);

    $this->actingAs($user)
        ->post('/profile/sessions/logout-others', ['password' => 'correct'])
        ->assertRedirect();

    expect(DB::table('sessions')->where('user_id', $user->id)->count())->toBeLessThanOrEqual(1);
});
