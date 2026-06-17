<?php

declare(strict_types=1);

use App\Enums\AuditAction;
use App\Enums\Permission as PermissionEnum;
use App\Jobs\ExportAuditLog;
use App\Models\AuditLog;
use App\Models\Permission;
use App\Notifications\AuditExportReady;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->tenant = makeTenant();
});

it('writes a csv to storage and notifies the requester', function () {
    Storage::fake('local');
    Notification::fake();

    $admin = makeUserWithRole($this->tenant, 'tenant-admin');

    foreach (range(1, 3) as $i) {
        AuditLog::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $admin->id,
            'action' => AuditAction::Created->value,
            'created_at' => now(),
        ]);
    }

    (new ExportAuditLog($this->tenant->id, $admin->id))->handle();

    $files = Storage::disk('local')->files("audit-exports/{$this->tenant->id}");
    expect($files)->toHaveCount(1);

    $contents = Storage::disk('local')->get($files[0]);
    expect($contents)->toContain('id,action,user,auditable,ip,created_at');

    Notification::assertSentTo($admin, AuditExportReady::class);
});

it('serves a stored export through a signed download link', function () {
    Storage::fake('local');

    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $permission = Permission::query()->where('slug', PermissionEnum::AuditExport->value)->firstOrFail();
    $admin->directPermissions()->attach($permission->id, ['type' => 'grant']);

    $filename = 'audit-acme-20260617-120000.csv';
    Storage::disk('local')->put("audit-exports/{$this->tenant->id}/{$filename}", "id,action\n1,test\n");

    $url = URL::temporarySignedRoute('admin.audit.export.download', now()->addDay(), [
        'tenant' => $this->tenant->slug,
        'filename' => $filename,
    ]);

    $this->actingAs($admin->fresh())
        ->get($url)
        ->assertOk()
        ->assertDownload($filename);
});

it('rejects an unsigned download link', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $permission = Permission::query()->where('slug', PermissionEnum::AuditExport->value)->firstOrFail();
    $admin->directPermissions()->attach($permission->id, ['type' => 'grant']);

    $this->actingAs($admin->fresh())
        ->get(route('admin.audit.export.download', [
            'tenant' => $this->tenant->slug,
            'filename' => 'whatever.csv',
        ]))
        ->assertStatus(403);
});
