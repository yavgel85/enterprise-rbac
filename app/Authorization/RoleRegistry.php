<?php

declare(strict_types=1);

namespace App\Authorization;

use App\Enums\Permission;
use InvalidArgumentException;

final class RoleRegistry
{
    /**
     * @return array<string, RoleDefinition>
     */
    public static function all(): array
    {
        return [
            'tenant-admin' => new RoleDefinition(
                slug: 'tenant-admin',
                name: 'Tenant Administrator',
                level: 90,
                permissions: Permission::cases(),
                description: 'Full access within the tenant',
                parentSlug: 'manager',
            ),
            'manager' => new RoleDefinition(
                slug: 'manager',
                name: 'Manager',
                level: 70,
                permissions: [
                    Permission::UsersView,
                    Permission::DepartmentsView,
                    Permission::CompaniesView,
                    Permission::CompaniesCreate,
                    Permission::CompaniesUpdate,
                    Permission::ContactsView,
                    Permission::ContactsCreate,
                    Permission::ContactsUpdate,
                    Permission::DealsView,
                    Permission::DealsCreate,
                    Permission::DealsUpdate,
                    Permission::DealsApprove,
                    Permission::TasksView,
                    Permission::TasksCreate,
                    Permission::TasksUpdate,
                    Permission::TasksComplete,
                    Permission::ActivitiesView,
                    Permission::ActivitiesCreate,
                    Permission::ActivitiesUpdate,
                    Permission::AuditView,
                    Permission::ApprovalsView,
                    Permission::ReportsView,
                ],
                description: 'CRM full access + audit visibility',
                parentSlug: 'sales',
            ),
            'sales' => new RoleDefinition(
                slug: 'sales',
                name: 'Sales Representative',
                level: 40,
                permissions: [
                    Permission::CompaniesView,
                    Permission::CompaniesCreate,
                    Permission::CompaniesUpdate,
                    Permission::ContactsView,
                    Permission::ContactsCreate,
                    Permission::ContactsUpdate,
                    Permission::DealsView,
                    Permission::DealsCreate,
                    Permission::DealsUpdate,
                    Permission::TasksView,
                    Permission::TasksCreate,
                    Permission::TasksUpdate,
                    Permission::TasksComplete,
                    Permission::ActivitiesView,
                    Permission::ActivitiesCreate,
                    Permission::ActivitiesUpdate,
                ],
                description: 'CRM operations without approve or delete',
                parentSlug: 'viewer',
            ),
            'auditor' => new RoleDefinition(
                slug: 'auditor',
                name: 'Auditor',
                level: 30,
                permissions: [
                    Permission::CompaniesView,
                    Permission::ContactsView,
                    Permission::DealsView,
                    Permission::TasksView,
                    Permission::ActivitiesView,
                    Permission::AuditView,
                    Permission::AuditExport,
                ],
                description: 'Read-only access plus full audit log',
            ),
            'viewer' => new RoleDefinition(
                slug: 'viewer',
                name: 'Viewer',
                level: 10,
                permissions: [
                    Permission::CompaniesView,
                    Permission::ContactsView,
                    Permission::DealsView,
                    Permission::TasksView,
                    Permission::ActivitiesView,
                ],
                description: 'Read-only access to CRM data',
            ),
        ];
    }

    public static function get(string $slug): RoleDefinition
    {
        return self::all()[$slug]
            ?? throw new InvalidArgumentException("Unknown role definition [{$slug}].");
    }

    /**
     * @return list<string>
     */
    public static function slugs(): array
    {
        return array_keys(self::all());
    }
}
