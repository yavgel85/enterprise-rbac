<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\AuditLog;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\AuditExportReady;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * Builds an audit-log CSV export off the request cycle (Improvement 4.2).
 *
 * The synchronous streamed download choked on tenants with >100k rows. This job
 * streams rows to a file on the `local` disk and then notifies the requester
 * with a short-lived signed download link.
 */
class ExportAuditLog implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 600;

    public int $tries = 3;

    /**
     * @param  array{action?: ?string, user_id?: ?int, from?: ?string, to?: ?string}  $filters
     */
    public function __construct(
        public int $tenantId,
        public int $requestedById,
        public array $filters = [],
    ) {}

    public function handle(): void
    {
        $tenant = Tenant::query()->withoutGlobalScopes()->find($this->tenantId);
        $requester = User::query()->withoutGlobalScopes()->find($this->requestedById);

        if (! $tenant || ! $requester) {
            return;
        }

        $directory = "audit-exports/{$tenant->id}";
        $filename = "audit-{$tenant->slug}-".now()->format('Ymd-His').'.csv';
        $disk = Storage::disk('local');
        $disk->makeDirectory($directory);

        $handle = fopen($disk->path("{$directory}/{$filename}"), 'w');
        fputcsv($handle, ['id', 'action', 'user', 'auditable', 'ip', 'created_at']);

        $this->baseQuery()
            ->with('user:id,email')
            ->orderBy('id')
            ->lazyById()
            ->each(function (AuditLog $log) use ($handle): void {
                fputcsv($handle, [
                    $log->id,
                    $log->action,
                    $log->user?->email,
                    $log->auditable_type ? $log->auditable_type.'#'.$log->auditable_id : null,
                    $log->ip_address,
                    $log->created_at?->format('Y-m-d H:i:s'),
                ]);
            });

        fclose($handle);

        $requester->notify(new AuditExportReady($tenant, $filename));
    }

    private function baseQuery(): Builder
    {
        $query = AuditLog::query()->where('tenant_id', $this->tenantId);

        if (! empty($this->filters['action'])) {
            $query->where('action', $this->filters['action']);
        }

        if (! empty($this->filters['user_id'])) {
            $query->where('user_id', (int) $this->filters['user_id']);
        }

        if (! empty($this->filters['from'])) {
            $query->where('created_at', '>=', Carbon::parse($this->filters['from'])->startOfDay());
        }

        if (! empty($this->filters['to'])) {
            $query->where('created_at', '<=', Carbon::parse($this->filters['to'])->endOfDay());
        }

        return $query;
    }
}
