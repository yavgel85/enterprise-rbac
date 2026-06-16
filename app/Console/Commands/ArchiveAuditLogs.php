<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ArchiveAuditLogs extends Command
{
    protected $signature = 'audit:archive {--tenant= : Restrict to a single tenant slug} {--dry-run : Report what would be archived without writing or deleting}';

    protected $description = 'Archive audit logs older than the retention window to JSONL files and prune them from the database.';

    public function handle(): int
    {
        $defaultDays = (int) config('audit.retention.default_days', 90);
        $disk = Storage::disk(config('audit.retention.disk', 'local'));
        $basePath = trim((string) config('audit.retention.path', 'audit-archive'), '/');
        $dryRun = (bool) $this->option('dry-run');

        $tenants = Tenant::query()
            ->when($this->option('tenant'), fn ($q, $slug) => $q->where('slug', $slug))
            ->get();

        $totalArchived = 0;

        foreach ($tenants as $tenant) {
            $days = (int) ($tenant->settings['audit_retention_days'] ?? $defaultDays);

            if ($days <= 0) {
                $this->line("Tenant {$tenant->slug}: retention disabled (days={$days}), skipping.");

                continue;
            }

            $cutoff = now()->subDays($days);

            $query = AuditLog::query()
                ->where('tenant_id', $tenant->id)
                ->where('created_at', '<', $cutoff);

            $count = (clone $query)->count();

            if ($count === 0) {
                continue;
            }

            if ($dryRun) {
                $this->line("Tenant {$tenant->slug}: would archive {$count} rows older than {$cutoff->toDateString()}.");
                $totalArchived += $count;

                continue;
            }

            $file = "{$basePath}/{$tenant->slug}/".now()->format('Y-m-d_His').'.jsonl';
            $handle = fopen('php://temp', 'r+');

            $query->orderBy('id')->lazyById()->each(function (AuditLog $log) use ($handle): void {
                fwrite($handle, json_encode($log->getAttributes(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE).PHP_EOL);
            });

            rewind($handle);
            $disk->put($file, stream_get_contents($handle) ?: '');
            fclose($handle);

            DB::transaction(function () use ($tenant, $cutoff): void {
                AuditLog::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('created_at', '<', $cutoff)
                    ->delete();
            });

            AuditLog::create([
                'tenant_id' => $tenant->id,
                'user_id' => null,
                'action' => AuditAction::AuditArchived->value,
                'metadata' => ['rows' => $count, 'file' => $file, 'cutoff' => $cutoff->toIso8601String()],
                'created_at' => now(),
            ]);

            $this->info("Tenant {$tenant->slug}: archived {$count} rows to {$file}.");
            $totalArchived += $count;
        }

        $this->info(($dryRun ? '[dry-run] ' : '')."Done. {$totalArchived} rows ".($dryRun ? 'eligible' : 'archived').'.');

        return self::SUCCESS;
    }
}
