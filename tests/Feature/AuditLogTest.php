<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Company;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('writes an audit entry when an auditable model is created via http', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    $this->actingAs($admin)
        ->post(route('crm.companies.store', $this->tenant), [
            'name' => 'Audit Co',
            'status' => 'active',
        ])->assertRedirect();

    $log = AuditLog::query()->where('action', AuditAction::Created->value)->latest('id')->first();

    expect($log)->not->toBeNull()
        ->and($log->user_id)->toBe($admin->id)
        ->and($log->tenant_id)->toBe($this->tenant->id)
        ->and($log->auditable_type)->toBe(Company::class);
});

it('records permission denials via the CheckPermission middleware path', function () {
    $viewer = makeUserWithRole($this->tenant, 'viewer');
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    $this->actingAs($viewer)
        ->delete(route('crm.companies.destroy', [$this->tenant, Company::factory()->create([
            'tenant_id' => $this->tenant->id,
        ])]))
        ->assertForbidden();

    expect($admin)->not->toBeNull();
});

it('records successful logins', function () {
    $user = makeUserWithRole($this->tenant, 'sales', ['password' => 'password']);

    $this->post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertRedirect();

    expect(AuditLog::query()->where('action', AuditAction::Login->value)->where('user_id', $user->id)->exists())
        ->toBeTrue();
});
