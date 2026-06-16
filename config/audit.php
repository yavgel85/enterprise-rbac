<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Structured audit log channel (Improvement 3.3)
    |--------------------------------------------------------------------------
    | Every persisted AuditLog row is mirrored to this Monolog channel as a
    | structured record. Point the channel at an external collector
    | (Sentry breadcrumb, Datadog agent, OpenTelemetry, rsyslog) via the
    | logging config / env to ship audit events off-box. Set to null to
    | disable structured forwarding entirely.
    */
    'log_channel' => env('AUDIT_LOG_CHANNEL', 'audit'),

    /*
    |--------------------------------------------------------------------------
    | Retention & archive (Improvement 3.4)
    |--------------------------------------------------------------------------
    | The audit:archive command moves rows older than the retention window to
    | a JSONL file (one per tenant per run) on the configured disk and deletes
    | them from the database. Each tenant may override the window through
    | tenants.settings.audit_retention_days; otherwise default_days is used.
    */
    'retention' => [
        'default_days' => (int) env('AUDIT_RETENTION_DAYS', 90),
        'disk' => env('AUDIT_ARCHIVE_DISK', 'local'),
        'path' => env('AUDIT_ARCHIVE_PATH', 'audit-archive'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Per-tenant audit sinks (Improvement 3.5)
    |--------------------------------------------------------------------------
    | When enabled, each persisted audit row is fanned out to a tenant's active
    | sinks (currently webhook) via a queued job. Deliveries are signed with an
    | HMAC-SHA256 signature derived from the sink secret.
    */
    'sinks' => [
        'enabled' => (bool) env('AUDIT_SINKS_ENABLED', true),
        'timeout' => (int) env('AUDIT_SINK_TIMEOUT', 5),
        'tries' => (int) env('AUDIT_SINK_TRIES', 3),
    ],
];
