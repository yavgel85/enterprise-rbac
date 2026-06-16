<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditSink;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Throwable;

class DeliverAuditLogToSink implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<string, mixed>  $payload
     */
    public function __construct(
        public int $sinkId,
        public array $payload,
    ) {}

    public function tries(): int
    {
        return (int) config('audit.sinks.tries', 3);
    }

    public function handle(): void
    {
        $sink = AuditSink::query()->find($this->sinkId);

        if ($sink === null || ! $sink->is_active) {
            return;
        }

        $body = json_encode($this->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '{}';
        $headers = ['Content-Type' => 'application/json'];

        if ($sink->secret) {
            $headers['X-Audit-Signature'] = 'sha256='.hash_hmac('sha256', $body, $sink->secret);
        }

        try {
            $response = Http::timeout((int) config('audit.sinks.timeout', 5))
                ->withHeaders($headers)
                ->withBody($body, 'application/json')
                ->post($sink->endpoint);

            if ($response->failed()) {
                $this->markFailed($sink, "HTTP {$response->status()}");

                return;
            }

            $sink->forceFill([
                'last_delivered_at' => now(),
                'last_error' => null,
            ])->save();
        } catch (Throwable $e) {
            $this->markFailed($sink, $e->getMessage());

            throw $e;
        }
    }

    private function markFailed(AuditSink $sink, string $error): void
    {
        $sink->forceFill([
            'last_failed_at' => now(),
            'last_error' => mb_substr($error, 0, 255),
        ])->save();
    }
}
