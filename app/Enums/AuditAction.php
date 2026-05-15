<?php

declare(strict_types=1);

namespace App\Enums;

enum AuditAction: string
{
    case Created = 'created';
    case Updated = 'updated';
    case Deleted = 'deleted';
    case Restored = 'restored';

    case Login = 'login';
    case Logout = 'logout';
    case LoginFailed = 'login_failed';

    case PermissionDenied = 'permission_denied';
    case RolesAssigned = 'roles_assigned';
    case RoleRevoked = 'role_revoked';
    case PermissionGranted = 'permission_granted';
    case PermissionRevoked = 'permission_revoked';

    case InvitationSent = 'invitation_sent';
    case InvitationAccepted = 'invitation_accepted';

    case TenantBootstrapped = 'tenant_bootstrapped';
    case TenantSuspended = 'tenant_suspended';
    case TenantActivated = 'tenant_activated';

    case DealApproved = 'deal_approved';
    case TaskCompleted = 'task_completed';
}
