<?php

declare(strict_types=1);

use App\Actions\Authorization\PermissionUsageReport;
use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\User;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('reports granted role and direct-user counts per permission', function () {
    // viewer role holds deals.view in the seeded chain.
    makeUserWithRole($this->tenant, 'viewer');

    $report = app(PermissionUsageReport::class)->handle(fresh: true);

    expect($report)->toHaveKey('deals.view');
    expect($report['deals.view']['granted_roles'])->toBeGreaterThan(0);
});

it('counts permission denials within the window', function () {
    $user = makeUserWithRole($this->tenant, 'viewer');

    AuditLog::create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $user->id,
        'action' => AuditAction::PermissionDenied->value,
        'metadata' => ['permission' => 'deals.delete', 'route' => 'x'],
        'created_at' => now(),
    ]);

    $report = app(PermissionUsageReport::class)->handle(fresh: true);

    expect($report['deals.delete']['denied'])->toBe(1);
});

it('ignores denials older than the window', function () {
    $user = makeUserWithRole($this->tenant, 'viewer');

    AuditLog::create([
        'tenant_id' => $this->tenant->id,
        'user_id' => $user->id,
        'action' => AuditAction::PermissionDenied->value,
        'metadata' => ['permission' => 'deals.delete'],
        'created_at' => now()->subDays(60),
    ]);

    $report = app(PermissionUsageReport::class)->handle(fresh: true);

    expect($report['deals.delete']['denied'])->toBe(0);
});

it('runs the rbac:usage command', function () {
    makeUserWithRole($this->tenant, 'viewer');

    $this->artisan('rbac:usage')
        ->assertExitCode(0);
});

it('shows usage stats on the super-admin permissions catalog', function () {
    makeUserWithRole($this->tenant, 'viewer');
    $super = User::factory()->superAdmin()->create();

    $this->actingAs($super)
        ->get(route('super-admin.permissions.index'))
        ->assertOk()
        ->assertSee('Denied');
});
