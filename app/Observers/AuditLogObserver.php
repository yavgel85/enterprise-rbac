<?php

declare(strict_types=1);

namespace App\Observers;

use App\Jobs\DeliverAuditLogToSink;
use App\Models\AuditLog;
use App\Models\AuditSink;
use Illuminate\Support\Facades\Log;

class AuditLogObserver
{
    /**
     * Fan a freshly persisted audit row out to observability targets:
     *  - a structured Monolog channel (3.3),
     *  - any active per-tenant sinks (3.5).
     */
    public function created(AuditLog $log): void
    {
        $this->forwardToLogChannel($log);
        $this->dispatchToSinks($log);
    }

    private function forwardToLogChannel(AuditLog $log): void
    {
        $channel = config('audit.log_channel');

        if (empty($channel)) {
            return;
        }

        Log::channel($channel)->info('audit.'.$log->action, $this->payload($log));
    }

    private function dispatchToSinks(AuditLog $log): void
    {
        if (! config('audit.sinks.enabled') || $log->tenant_id === null) {
            return;
        }

        $payload = $this->payload($log);

        AuditSink::query()
            ->where('tenant_id', $log->tenant_id)
            ->where('is_active', true)
            ->get()
            ->filter(fn (AuditSink $sink) => $sink->listensTo($log->action))
            ->each(fn (AuditSink $sink) => DeliverAuditLogToSink::dispatch($sink->id, $payload));
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(AuditLog $log): array
    {
        return [
            'id' => $log->id,
            'tenant_id' => $log->tenant_id,
            'user_id' => $log->user_id,
            'action' => $log->action,
            'auditable_type' => $log->auditable_type,
            'auditable_id' => $log->auditable_id,
            'old_values' => $log->old_values,
            'new_values' => $log->new_values,
            'metadata' => $log->metadata,
            'ip_address' => $log->ip_address,
            'url' => $log->url,
            'created_at' => $log->created_at?->toAtomString(),
        ];
    }
}
