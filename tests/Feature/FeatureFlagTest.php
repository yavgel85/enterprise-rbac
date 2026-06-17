<?php

declare(strict_types=1);

use App\Enums\Permission as PermissionEnum;
use App\Jobs\ExportAuditLog;
use App\Models\Feature;
use App\Models\Permission;
use Illuminate\Support\Facades\Queue;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('blocks audit export when the feature is disabled', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $permission = Permission::query()->where('slug', PermissionEnum::AuditExport->value)->firstOrFail();
    $admin->directPermissions()->attach($permission->id, ['type' => 'grant']);

    $this->actingAs($admin)
        ->post(route('admin.audit.export', $this->tenant))
        ->assertRedirect()
        ->assertSessionHas('error');
});

it('queues an audit export job when the feature is enabled', function () {
    Queue::fake();

    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    $feature = Feature::query()->where('slug', 'audit_export')->firstOrFail();
    $this->tenant->features()->attach($feature->id, ['is_enabled' => true]);

    $permission = Permission::query()->where('slug', PermissionEnum::AuditExport->value)->firstOrFail();
    $admin->directPermissions()->attach($permission->id, ['type' => 'grant']);

    $this->actingAs($admin)
        ->post(route('admin.audit.export', $this->tenant))
        ->assertRedirect()
        ->assertSessionHas('status');

    Queue::assertPushed(ExportAuditLog::class, fn (ExportAuditLog $job) => $job->tenantId === $this->tenant->id
        && $job->requestedById === $admin->id);
});
