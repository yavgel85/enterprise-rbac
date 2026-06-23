<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Storage disk
    |--------------------------------------------------------------------------
    | Files are stored tenant-scoped under tenants/{tenant_id}/attachments/...
    | on this disk. Use the private "local" disk so files are only reachable
    | through the signed, permission-gated download route.
    */
    'disk' => env('ATTACHMENTS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Per-file maximum size (kilobytes)
    |--------------------------------------------------------------------------
    */
    'max_file_kb' => (int) env('ATTACHMENTS_MAX_FILE_KB', 10240),

    /*
    |--------------------------------------------------------------------------
    | Per-tenant storage quota (megabytes)
    |--------------------------------------------------------------------------
    | Total bytes a tenant may consume across all attachments.
    */
    'tenant_quota_mb' => (int) env('ATTACHMENTS_TENANT_QUOTA_MB', 1024),

    /*
    |--------------------------------------------------------------------------
    | Signed download link TTL (minutes)
    |--------------------------------------------------------------------------
    */
    'download_ttl_minutes' => (int) env('ATTACHMENTS_DOWNLOAD_TTL', 30),
];
