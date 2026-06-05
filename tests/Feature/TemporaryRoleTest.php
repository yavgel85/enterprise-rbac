<?php

declare(strict_types=1);

use App\Actions\Authorization\ForgetUserPermissionsCache;
use App\Actions\Authorization\GrantTemporaryRole;
use App\Actions\Authorization\ResolveUserPermissions;
use App\Models\User;
use Illuminate\Support\Carbon;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('grants a role that expires after the given hours without removing existing roles', function () {
    $admin = User::factory()->superAdmin()->create();
    $member = makeUserWithRole($this->tenant, 'viewer');
    $manager = tenantRole($this->tenant, 'manager');

    app(GrantTemporaryRole::class)->handle($admin, $member, $manager->id, 6);

    $member->load('roles');
    expect($member->roles->pluck('slug'))->toContain('viewer', 'manager');

    $pivot = $member->roles->firstWhere('id', $manager->id)->pivot;
    expect($pivot->expires_at)->not->toBeNull();
    expect(Carbon::parse($pivot->expires_at)->isFuture())->toBeTrue();
});

it('drops the temporary permissions once expired', function () {
    $admin = User::factory()->superAdmin()->create();
    $member = makeUserWithRole($this->tenant, 'viewer');
    $manager = tenantRole($this->tenant, 'manager');

    app(GrantTemporaryRole::class)->handle($admin, $member, $manager->id, 6);
    expect(app(ResolveUserPermissions::class)->handle($member->fresh()))->toHaveKey('deals.approve');

    // Travel past expiry.
    $this->travel(7)->hours();
    app(ForgetUserPermissionsCache::class)->forUser($member);

    expect(app(ResolveUserPermissions::class)->handle($member->fresh()))->not->toHaveKey('deals.approve');
});

it('rejects fewer than one hour', function () {
    $admin = User::factory()->superAdmin()->create();
    $member = makeUserWithRole($this->tenant, 'viewer');
    $manager = tenantRole($this->tenant, 'manager');

    expect(fn () => app(GrantTemporaryRole::class)->handle($admin, $member, $manager->id, 0))
        ->toThrow(DomainException::class);
});

it('prevents granting a role at or above the actor level', function () {
    $manager = makeUserWithRole($this->tenant, 'manager'); // level 70
    $member = makeUserWithRole($this->tenant, 'viewer');
    $admin = tenantRole($this->tenant, 'tenant-admin');     // level 90

    expect(fn () => app(GrantTemporaryRole::class)->handle($manager, $member, $admin->id, 4))
        ->toThrow(DomainException::class);
});

it('grants a temporary role from the UI', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $member = makeUserWithRole($this->tenant, 'viewer');
    $manager = tenantRole($this->tenant, 'manager');

    $this->actingAs($admin)
        ->post("/t/{$this->tenant->slug}/admin/users/{$member->id}/roles/temporary", [
            'role_id' => $manager->id,
            'hours' => 8,
        ])
        ->assertRedirect();

    expect($member->fresh()->roles->firstWhere('id', $manager->id)->pivot->expires_at)->not->toBeNull();
});
