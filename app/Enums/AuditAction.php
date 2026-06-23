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

    case AccountLocked = 'account_locked';
    case AccountUnlocked = 'account_unlocked';

    case PasswordResetRequested = 'password_reset_requested';
    case PasswordResetCompleted = 'password_reset_completed';
    case PasswordChangedBySelf = 'password_changed_by_self';
    case PasswordChangedByAdmin = 'password_changed_by_admin';

    case EmailVerificationSent = 'email_verification_sent';
    case EmailVerified = 'email_verified';

    case SessionTerminated = 'session_terminated';

    case PermissionConditionCreated = 'permission_condition_created';
    case PermissionConditionDeleted = 'permission_condition_deleted';

    case ResourcePermissionGranted = 'resource_permission_granted';
    case ResourcePermissionRevoked = 'resource_permission_revoked';

    case ApprovalRequested = 'approval_requested';
    case ApprovalStepApproved = 'approval_step_approved';
    case ApprovalStepRejected = 'approval_step_rejected';
    case ApprovalCompleted = 'approval_completed';

    case AuditSinkCreated = 'audit_sink_created';
    case AuditSinkUpdated = 'audit_sink_updated';
    case AuditSinkDeleted = 'audit_sink_deleted';
    case AuditArchived = 'audit_archived';

    case ReportExported = 'report_exported';

    case AttachmentUploaded = 'attachment_uploaded';
    case AttachmentDeleted = 'attachment_deleted';

    case CustomFieldCreated = 'custom_field_created';
    case CustomFieldUpdated = 'custom_field_updated';
    case CustomFieldDeleted = 'custom_field_deleted';
}
