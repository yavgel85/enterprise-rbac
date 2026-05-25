<?php

declare(strict_types=1);

use App\Actions\Authorization\AssertPasswordNotReused;
use App\Actions\Authorization\RecordPasswordHistory;
use App\Models\Invitation;
use App\Models\PasswordHistory;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

beforeEach(function () {
    config()->set('rbac.password_history.size', 3);
});

it('records new password hashes and prunes the tail to history size', function () {
    $user = User::factory()->create(['password' => 'first-password']);
    $record = app(RecordPasswordHistory::class);

    foreach (['second', 'third', 'fourth', 'fifth'] as $i => $pw) {
        $record->handle($user, $pw);
    }

    $hashes = $user->passwordHistories()->pluck('password_hash')->all();

    expect($hashes)->toHaveCount(3);
    expect(Hash::check('fifth', $hashes[0]))->toBeTrue();
    expect(Hash::check('fourth', $hashes[1]))->toBeTrue();
    expect(Hash::check('third', $hashes[2]))->toBeTrue();
});

it('rejects the current password as a reuse', function () {
    $user = User::factory()->create(['password' => 'current-pw']);

    expect(fn () => app(AssertPasswordNotReused::class)->handle($user, 'current-pw'))
        ->toThrow(DomainException::class);
});

it('rejects passwords inside the history window', function () {
    $user = User::factory()->create(['password' => 'current-pw']);

    app(RecordPasswordHistory::class)->handle($user, 'prev-1');
    app(RecordPasswordHistory::class)->handle($user, 'prev-2');

    expect(fn () => app(AssertPasswordNotReused::class)->handle($user, 'prev-1'))
        ->toThrow(DomainException::class);
});

it('accepts a password that fell outside the history window', function () {
    $user = User::factory()->create(['password' => 'current-pw']);

    // size = 3 → 'ancient' is the oldest after we record 3 newer ones.
    app(RecordPasswordHistory::class)->handle($user, 'ancient');
    app(RecordPasswordHistory::class)->handle($user, 'fresh-1');
    app(RecordPasswordHistory::class)->handle($user, 'fresh-2');
    app(RecordPasswordHistory::class)->handle($user, 'fresh-3');

    app(AssertPasswordNotReused::class)->handle($user, 'ancient');

    expect(true)->toBeTrue();
});

it('is a no-op when history size is zero', function () {
    config()->set('rbac.password_history.size', 0);

    $user = User::factory()->create(['password' => 'current-pw']);
    app(RecordPasswordHistory::class)->handle($user, 'current-pw');

    expect(PasswordHistory::query()->where('user_id', $user->id)->count())->toBe(0);

    app(AssertPasswordNotReused::class)->handle($user, 'current-pw');

    expect(true)->toBeTrue();
});

it('blocks self password change when the new password is in history', function () {
    $user = User::factory()->create(['password' => 'current-pw']);
    app(RecordPasswordHistory::class)->handle($user, 'used-before');

    $this->actingAs($user)
        ->put('/profile/password', [
            'current_password' => 'current-pw',
            'password' => 'used-before',
            'password_confirmation' => 'used-before',
        ])
        ->assertSessionHasErrors('current_password');

    expect(Hash::check('current-pw', $user->fresh()->password))->toBeTrue();
});

it('blocks admin override when the candidate password is in history', function () {
    $tenant = makeTenant();
    $admin = makeUserWithRole($tenant, 'tenant-admin');
    $sales = makeUserWithRole($tenant, 'sales', ['password' => 'sales-old']);

    app(RecordPasswordHistory::class)->handle($sales, 'recycled-pw');

    $this->actingAs($admin)
        ->put("/t/{$tenant->slug}/admin/users/{$sales->id}/password", [
            'password' => 'recycled-pw',
            'password_confirmation' => 'recycled-pw',
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Hash::check('sales-old', $sales->fresh()->password))->toBeTrue();
});

it('blocks password reset when the candidate password is in history (without spending the token)', function () {
    $user = User::factory()->create(['password' => 'current-pw']);
    app(RecordPasswordHistory::class)->handle($user, 'recycled-pw');

    $token = Password::createToken($user);

    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'recycled-pw',
        'password_confirmation' => 'recycled-pw',
    ])->assertSessionHasErrors('password');

    expect(Hash::check('current-pw', $user->fresh()->password))->toBeTrue();

    // The token should still be valid for a non-reused password.
    $this->post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'totally-fresh-pw',
        'password_confirmation' => 'totally-fresh-pw',
    ])->assertRedirect(route('login'));

    expect(Hash::check('totally-fresh-pw', $user->fresh()->password))->toBeTrue();
});

it('records the chosen password when an invitation is accepted', function () {
    $tenant = makeTenant();
    $admin = makeUserWithRole($tenant, 'tenant-admin');
    $role = tenantRole($tenant, 'sales');

    $invitation = Invitation::create([
        'tenant_id' => $tenant->id,
        'email' => 'fresh@x.test',
        'role_id' => $role->id,
        'token' => 'plain-token-'.uniqid(),
        'invited_by' => $admin->id,
        'expires_at' => now()->addDays(7),
    ]);

    $this->post("/invitations/{$invitation->token}", [
        'name' => 'Fresh User',
        'password' => 'initial-pw',
        'password_confirmation' => 'initial-pw',
    ])->assertRedirect();

    $user = User::query()->where('email', 'fresh@x.test')->firstOrFail();

    expect($user->passwordHistories()->count())->toBe(1);
    expect(Hash::check('initial-pw', $user->passwordHistories->first()->password_hash))->toBeTrue();
});
