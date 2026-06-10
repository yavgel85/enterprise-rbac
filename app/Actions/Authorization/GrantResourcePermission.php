<?php

declare(strict_types=1);

namespace App\Actions\Authorization;

use App\Actions\Audit\LogAuditEvent;
use App\Enums\AuditAction;
use App\Models\Permission;
use App\Models\ResourcePermission;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Database\Eloquent\Model;

final readonly class GrantResourcePermission
{
    public function __construct(private LogAuditEvent $audit) {}

    public function handle(
        User $actor,
        User $user,
        Permission $permission,
        Model $resource,
        ?DateTimeInterface $expiresAt = null,
    ): ResourcePermission {
        $grant = ResourcePermission::updateOrCreate(
            [
                'user_id' => $user->id,
                'permission_id' => $permission->id,
                'resource_type' => $resource->getMorphClass(),
                'resource_id' => $resource->getKey(),
            ],
            [
                'tenant_id' => $resource->tenant_id ?? $user->tenant_id,
                'expires_at' => $expiresAt,
                'assigned_by' => $actor->id,
            ],
        );

        $this->audit->handle(AuditAction::ResourcePermissionGranted, $resource, [
            'user_id' => $user->id,
            'permission' => $permission->slug,
            'expires_at' => $expiresAt?->format(DateTimeInterface::ATOM),
        ]);

        return $grant;
    }
}
