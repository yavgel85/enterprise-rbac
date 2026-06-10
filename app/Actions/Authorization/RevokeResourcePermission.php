<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Models\ResourcePermission;

final readonly class RevokeResourcePermission
{
    public function __construct(private LogAuditEvent $audit) {}

    public function handle(ResourcePermission $grant): void
    {
        $metadata = [
            'user_id' => $grant->user_id,
            'permission_id' => $grant->permission_id,
            'resource_type' => $grant->resource_type,
            'resource_id' => $grant->resource_id,
        ];

        $grant->delete();

        $this->audit->handle(AuditAction::ResourcePermissionRevoked, null, $metadata);
    }
}
