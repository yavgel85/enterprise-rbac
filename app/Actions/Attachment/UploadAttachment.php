<?php

declare(strict_types=1);

namespace App\Actions\Attachment;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Models\Attachment;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;

final readonly class UploadAttachment
{
    public function __construct(private LogAuditEvent $audit) {}

    public function handle(User $actor, Model $attachable, UploadedFile $file): Attachment
    {
        $disk = config('attachments.disk');
        $tenantId = $attachable->getAttribute('tenant_id');

        $path = $file->store("tenants/{$tenantId}/attachments", $disk);

        $attachment = $attachable->attachments()->create([
            'tenant_id' => $tenantId,
            'disk' => $disk,
            'path' => $path,
            'name' => $file->getClientOriginalName(),
            'size' => $file->getSize(),
            'mime' => $file->getClientMimeType(),
            'uploaded_by' => $actor->id,
        ]);

        $this->audit->handle(AuditAction::AttachmentUploaded, $attachable, [
            'attachment_id' => $attachment->id,
            'name' => $attachment->name,
            'size' => $attachment->size,
        ]);

        return $attachment;
    }
}
