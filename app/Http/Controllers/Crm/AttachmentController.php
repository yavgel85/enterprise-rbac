<?php

declare(strict_types=1);

namespace App\Http\Controllers\Crm;

use App\Actions\Attachment\UploadAttachment;
use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\Contact;
use App\Models\Deal;
use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    /**
     * Whitelist of attachable short keys → model classes. Never accept a raw
     * class name from the request.
     *
     * @var array<string, class-string<Model>>
     */
    private const ATTACHABLES = [
        'company' => Company::class,
        'contact' => Contact::class,
        'deal' => Deal::class,
        'task' => Task::class,
    ];

    public function store(Request $request, UploadAttachment $upload, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'attachable_type' => ['required', 'string', 'in:'.implode(',', array_keys(self::ATTACHABLES))],
            'attachable_id' => ['required', 'integer'],
            'file' => ['required', 'file', 'max:'.config('attachments.max_file_kb')],
        ]);

        $attachable = $this->resolveAttachable($validated['attachable_type'], (int) $validated['attachable_id']);

        $this->authorize('update', $attachable);

        $file = $request->file('file');
        $quotaBytes = (int) config('attachments.tenant_quota_mb') * 1024 * 1024;
        $usedBytes = (int) Attachment::query()->where('tenant_id', $tenant->id)->sum('size');

        if ($usedBytes + $file->getSize() > $quotaBytes) {
            return back()->with('error', 'Storage quota exceeded for this tenant.');
        }

        $upload->handle($request->user(), $attachable, $file);

        return back()->with('status', 'File uploaded.');
    }

    public function download(Request $request, Tenant $tenant, Attachment $attachment): StreamedResponse
    {
        $attachable = $attachment->attachable;
        abort_if($attachable === null, 404);

        $this->authorize('view', $attachable);

        abort_unless(Storage::disk($attachment->disk)->exists($attachment->path), 404);

        return Storage::disk($attachment->disk)->download($attachment->path, $attachment->name);
    }

    public function destroy(Request $request, LogAuditEvent $audit, Tenant $tenant, Attachment $attachment): RedirectResponse
    {
        $attachable = $attachment->attachable;
        abort_if($attachable === null, 404);

        $this->authorize('update', $attachable);

        Storage::disk($attachment->disk)->delete($attachment->path);

        $audit->handle(AuditAction::AttachmentDeleted, $attachable, [
            'attachment_id' => $attachment->id,
            'name' => $attachment->name,
        ]);

        $attachment->delete();

        return back()->with('status', 'File deleted.');
    }

    private function resolveAttachable(string $type, int $id): Model
    {
        $class = self::ATTACHABLES[$type];

        return $class::query()->findOrFail($id);
    }
}
