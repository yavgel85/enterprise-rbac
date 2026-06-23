<?php

declare(strict_types=1);

use App\Models\Attachment;
use App\Models\Company;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->tenant = makeTenant();
    Storage::fake('local');
});

it('uploads a file to a company and records it', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $company = Company::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($admin)
        ->post(route('crm.attachments.store', $this->tenant), [
            'attachable_type' => 'company',
            'attachable_id' => $company->id,
            'file' => UploadedFile::fake()->create('contract.pdf', 12, 'application/pdf'),
        ])
        ->assertRedirect()
        ->assertSessionHas('status');

    $attachment = Attachment::query()->firstOrFail();
    expect($attachment->attachable_id)->toBe($company->id)
        ->and($attachment->name)->toBe('contract.pdf')
        ->and($attachment->tenant_id)->toBe($this->tenant->id);

    Storage::disk('local')->assertExists($attachment->path);
});

it('blocks upload without update permission on the parent', function () {
    $viewer = makeUserWithRole($this->tenant, 'viewer');
    $company = Company::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($viewer)
        ->post(route('crm.attachments.store', $this->tenant), [
            'attachable_type' => 'company',
            'attachable_id' => $company->id,
            'file' => UploadedFile::fake()->create('x.pdf', 5),
        ])
        ->assertForbidden();

    expect(Attachment::query()->count())->toBe(0);
});

it('enforces the tenant storage quota', function () {
    config(['attachments.tenant_quota_mb' => 0]);
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $company = Company::factory()->create(['tenant_id' => $this->tenant->id]);

    $this->actingAs($admin)
        ->post(route('crm.attachments.store', $this->tenant), [
            'attachable_type' => 'company',
            'attachable_id' => $company->id,
            'file' => UploadedFile::fake()->create('big.pdf', 50),
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Attachment::query()->count())->toBe(0);
});

it('downloads an attachment through a signed link and rejects unsigned', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $company = Company::factory()->create(['tenant_id' => $this->tenant->id]);
    Storage::disk('local')->put("tenants/{$this->tenant->id}/attachments/file.pdf", 'data');

    $attachment = Attachment::create([
        'tenant_id' => $this->tenant->id,
        'attachable_type' => Company::class,
        'attachable_id' => $company->id,
        'disk' => 'local',
        'path' => "tenants/{$this->tenant->id}/attachments/file.pdf",
        'name' => 'file.pdf',
        'size' => 4,
        'mime' => 'application/pdf',
        'uploaded_by' => $admin->id,
    ]);

    $url = URL::temporarySignedRoute('crm.attachments.download', now()->addMinutes(10), [
        'tenant' => $this->tenant->slug,
        'attachment' => $attachment->id,
    ]);

    $this->actingAs($admin)->get($url)->assertOk();

    $this->actingAs($admin)
        ->get(route('crm.attachments.download', [$this->tenant, $attachment]))
        ->assertForbidden();
});

it('deletes an attachment', function () {
    $admin = makeUserWithRole($this->tenant, 'tenant-admin');
    $company = Company::factory()->create(['tenant_id' => $this->tenant->id]);
    Storage::disk('local')->put("tenants/{$this->tenant->id}/attachments/d.pdf", 'data');

    $attachment = Attachment::create([
        'tenant_id' => $this->tenant->id,
        'attachable_type' => Company::class,
        'attachable_id' => $company->id,
        'disk' => 'local',
        'path' => "tenants/{$this->tenant->id}/attachments/d.pdf",
        'name' => 'd.pdf',
        'size' => 4,
        'uploaded_by' => $admin->id,
    ]);

    $this->actingAs($admin)
        ->delete(route('crm.attachments.destroy', [$this->tenant, $attachment]))
        ->assertRedirect();

    expect(Attachment::query()->count())->toBe(0);
    Storage::disk('local')->assertMissing($attachment->path);
});
