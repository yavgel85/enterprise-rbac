<?php

declare(strict_types=1);

namespace App\Actions\Audit;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Context;

final readonly class LogAuditEvent
{
    public function forModel(AuditAction $action, Model $model, array $metadata = []): void
    {
        if ($model instanceof AuditLog) {
            return;
        }

        $tenantId = $model->tenant_id ?? Context::get('tenant_id') ?? Auth::user()?->tenant_id;

        $oldValues = null;
        $newValues = null;

        if ($action === AuditAction::Updated) {
            $changes = $model->getChanges();
            unset($changes['updated_at']);
            if ($changes !== []) {
                $oldValues = collect($changes)
                    ->mapWithKeys(fn ($_, $key) => [$key => $model->getOriginal($key)])
                    ->all();
                $newValues = $changes;
            }
        } elseif ($action === AuditAction::Created || $action === AuditAction::Restored) {
            $newValues = $model->getAttributes();
        } elseif ($action === AuditAction::Deleted) {
            $oldValues = $model->getOriginal();
        }

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => Auth::id(),
            'action' => $action->value,
            'auditable_type' => $model::class,
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'url' => request()?->fullUrl(),
            'metadata' => $metadata !== [] ? $metadata : null,
            'created_at' => now(),
        ]);
    }

    public function handle(AuditAction $action, ?Model $subject = null, array $metadata = []): void
    {
        $tenantId = Context::get('tenant_id') ?? Auth::user()?->tenant_id;

        AuditLog::create([
            'tenant_id' => $tenantId,
            'user_id' => Auth::id(),
            'action' => $action->value,
            'auditable_type' => $subject?->getMorphClass(),
            'auditable_id' => $subject?->getKey(),
            'metadata' => $metadata !== [] ? $metadata : null,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'url' => request()?->fullUrl(),
            'created_at' => now(),
        ]);
    }
}
