<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

it('shows the forgot password form', function () {
    $this->get('/forgot-password')
        ->assertSuccessful()
        ->assertSee('Forgot your password?');
});

it('sends a password reset notification when the email exists', function () {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'reset@x.test',
    ]);

    $this->post('/forgot-password', ['email' => $user->email])
        ->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class);

    expect(AuditLog::query()->where('action', 'password_reset_requested')->exists())->toBeTrue();
});

it('silently succeeds for unknown emails (no enumeration)', function () {
    Notification::fake();

    $this->post('/forgot-password', ['email' => 'ghost@x.test'])
        ->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertNothingSent();
    expect(AuditLog::query()->where('action', 'password_reset_requested')->exists())->toBeFalse();
});

it('resets the password with a valid token and audits the event', function () {
    $user = User::factory()->create([
        'email' => 'change@x.test',
        'password' => 'old-password',
        'failed_login_attempts' => 3,
    ]);

    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'brand-new-pw',
        'password_confirmation' => 'brand-new-pw',
    ])->assertRedirect(route('login'));

    $fresh = $user->fresh();
    expect($fresh->failed_login_attempts)->toBe(0);
    expect($fresh->locked_until)->toBeNull();

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'brand-new-pw',
    ])->assertRedirect();
    $this->assertAuthenticatedAs($fresh);

    expect(AuditLog::query()->where('action', 'password_reset_completed')->exists())->toBeTrue();
});

it('rejects a tampered or expired token', function () {
    $user = User::factory()->create(['email' => 'change@x.test']);

    $this->post('/reset-password', [
        'token' => 'definitely-not-a-real-token',
        'email' => $user->email,
        'password' => 'brand-new-pw',
        'password_confirmation' => 'brand-new-pw',
    ])->assertSessionHasErrors('email');
});
