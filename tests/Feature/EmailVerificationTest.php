<?php

declare(strict_types=1);

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

it('shows the verify-email notice for unverified users', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/email/verify')
        ->assertSuccessful()
        ->assertSee('Verify your email');
});

it('redirects verified users away from the notice', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/email/verify')
        ->assertRedirect();
});

it('sends a verification notification on demand', function () {
    Notification::fake();

    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post('/email/verification-notification')
        ->assertRedirect();

    Notification::assertSentTo($user, VerifyEmail::class);
    expect(AuditLog::query()->where('action', 'email_verification_sent')->exists())->toBeTrue();
});

it('marks the email as verified when the signed link is opened', function () {
    Event::fake([Verified::class]);

    $user = User::factory()->unverified()->create();

    $url = URL::temporarySignedRoute(
        'verification.verify',
        Carbon::now()->addMinutes(60),
        ['id' => $user->id, 'hash' => sha1($user->getEmailForVerification())],
    );

    $this->actingAs($user)->get($url)->assertRedirect();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
    Event::assertDispatched(Verified::class);
    expect(AuditLog::query()->where('action', 'email_verified')->exists())->toBeTrue();
});

it('allows unverified users to sign in (soft mode) and shows the banner', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'soft@x.test',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticatedAs($user->fresh());
});
