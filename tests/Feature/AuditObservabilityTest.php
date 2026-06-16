<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Jobs\DeliverAuditLogToSink;
use App\Models\AuditLog;
use App\Models\AuditSink;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('lets a user with audit.manage create a sink but forbids an auditor', function () {
    Queue::fake();
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    $this->actingAs($admin)
        ->post(route('admin.audit-sinks.store', $this->tenant), [
            'name' => 'SIEM',
            'endpoint' => 'https://siem.example.com/audit',
            'secret' => 'topsecret',
        ])->assertRedirect();

    expect(AuditSink::query()->where('tenant_id', $this->tenant->id)->where('name', 'SIEM')->exists())->toBeTrue();

    $auditor = makeUserWithRole($this->tenant, 'auditor');

    $this->actingAs($auditor)
        ->get(route('admin.audit-sinks.index', $this->tenant))
        ->assertForbidden();
});

it('dispatches a delivery job to matching active sinks when an audit row is created', function () {
    AuditSink::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'all-events',
        'endpoint' => 'https://hook.example.com/a',
        'is_active' => true,
        'events' => null,
    ]);

    AuditSink::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'updates-only',
        'endpoint' => 'https://hook.example.com/b',
        'is_active' => true,
        'events' => [AuditAction::Updated->value],
    ]);

    Queue::fake();

    AuditLog::create([
        'tenant_id' => $this->tenant->id,
        'action' => AuditAction::Created->value,
        'created_at' => now(),
    ]);

    Queue::assertPushed(DeliverAuditLogToSink::class, 1);
});

it('signs and delivers the payload to the sink endpoint', function () {
    Http::fake(['*' => Http::response('', 200)]);

    $sink = AuditSink::create([
        'tenant_id' => $this->tenant->id,
        'name' => 'siem',
        'endpoint' => 'https://siem.example.com/audit',
        'secret' => 'shh',
        'is_active' => true,
    ]);

    (new DeliverAuditLogToSink($sink->id, ['action' => 'created', 'id' => 1]))->handle();

    Http::assertSent(fn ($request) => $request->url() === 'https://siem.example.com/audit'
        && str_starts_with($request->header('X-Audit-Signature')[0] ?? '', 'sha256='));

    expect($sink->fresh()->last_delivered_at)->not->toBeNull();
});

it('archives audit rows older than the retention window and prunes them', function () {
    Storage::fake('local');

    AuditLog::create([
        'tenant_id' => $this->tenant->id,
        'action' => AuditAction::Login->value,
        'created_at' => now()->subDays(200),
    ]);
    AuditLog::create([
        'tenant_id' => $this->tenant->id,
        'action' => AuditAction::Login->value,
        'created_at' => now()->subDay(),
    ]);

    $this->artisan('audit:archive')->assertSuccessful();

    expect(AuditLog::query()->where('action', AuditAction::Login->value)->count())->toBe(1)
        ->and(AuditLog::query()->where('action', AuditAction::AuditArchived->value)->exists())->toBeTrue()
        ->and(Storage::disk('local')->allFiles())->not->toBeEmpty();
});

it('respects a per-tenant retention override', function () {
    Storage::fake('local');
    $this->tenant->update(['settings' => ['audit_retention_days' => 0]]);

    AuditLog::create([
        'tenant_id' => $this->tenant->id,
        'action' => AuditAction::Login->value,
        'created_at' => now()->subDays(500),
    ]);

    $this->artisan('audit:archive')->assertSuccessful();

    expect(AuditLog::query()->where('action', AuditAction::Login->value)->count())->toBe(1);
});

it('filters the admin audit log by user', function () {
    $actor = makeUserWithRole($this->tenant, 'tenant-admin');
    $userA = makeUserWithRole($this->tenant, 'sales', ['email' => 'alpha-actor@example.test']);
    $userB = makeUserWithRole($this->tenant, 'sales', ['email' => 'bravo-actor@example.test']);

    AuditLog::create(['tenant_id' => $this->tenant->id, 'user_id' => $userA->id, 'action' => 'created', 'created_at' => now()]);
    AuditLog::create(['tenant_id' => $this->tenant->id, 'user_id' => $userB->id, 'action' => 'updated', 'created_at' => now()]);

    $this->actingAs($actor)
        ->get(route('admin.audit.index', [$this->tenant, 'user_id' => $userB->id]))
        ->assertOk()
        ->assertSee('>'.$userB->email.'<', false)
        ->assertDontSee('>'.$userA->email.'<', false);
});

it('requires password confirmation for destructive routes', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin', ['password' => 'password']);
    $role = tenantRole($this->tenant, 'viewer');

    $this->actingAs($admin)
        ->delete(route('admin.roles.destroy', [$this->tenant, $role]))
        ->assertRedirect(route('password.confirm'));

    $this->actingAs($admin)
        ->post(route('password.confirm'), ['password' => 'password'])
        ->assertRedirect();
});

it('serves the activity feed as json for permitted users and blocks others', function () {
    $auditor = makeUserWithRole($this->tenant, 'auditor');
    $viewer = makeUserWithRole($this->tenant, 'viewer');

    AuditLog::create(['tenant_id' => $this->tenant->id, 'user_id' => $auditor->id, 'action' => 'created', 'created_at' => now()]);

    $this->actingAs($auditor)
        ->getJson(route('tenant.activity-feed', $this->tenant))
        ->assertOk()
        ->assertJsonStructure(['events' => [['id', 'action', 'user', 'at']], 'latest_id']);

    $this->actingAs($viewer)
        ->getJson(route('tenant.activity-feed', $this->tenant))
        ->assertForbidden();
});

it('shows the super-admin observability dashboard only to super admins', function () {
    $super = User::factory()->create(['is_super_admin' => true, 'tenant_id' => null]);
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    $this->actingAs($super)
        ->get(route('super-admin.observability.index'))
        ->assertOk()
        ->assertSee('Observability');

    $this->actingAs($admin)
        ->get(route('super-admin.observability.index'))
        ->assertForbidden();
});
