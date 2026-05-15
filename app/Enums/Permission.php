<?php

declare(strict_types=1);

namespace App\Enums;

enum Permission: string
{
    case UsersView = 'users.view';
    case UsersCreate = 'users.create';
    case UsersUpdate = 'users.update';
    case UsersDelete = 'users.delete';
    case UsersInvite = 'users.invite';

    case RolesView = 'roles.view';
    case RolesCreate = 'roles.create';
    case RolesUpdate = 'roles.update';
    case RolesDelete = 'roles.delete';
    case RolesAssign = 'roles.assign';

    case PermissionsView = 'permissions.view';
    case PermissionsAssign = 'permissions.assign';

    case DepartmentsView = 'departments.view';
    case DepartmentsCreate = 'departments.create';
    case DepartmentsUpdate = 'departments.update';
    case DepartmentsDelete = 'departments.delete';

    case CompaniesView = 'companies.view';
    case CompaniesCreate = 'companies.create';
    case CompaniesUpdate = 'companies.update';
    case CompaniesDelete = 'companies.delete';

    case ContactsView = 'contacts.view';
    case ContactsCreate = 'contacts.create';
    case ContactsUpdate = 'contacts.update';
    case ContactsDelete = 'contacts.delete';

    case DealsView = 'deals.view';
    case DealsCreate = 'deals.create';
    case DealsUpdate = 'deals.update';
    case DealsDelete = 'deals.delete';
    case DealsApprove = 'deals.approve';
    case DealsExport = 'deals.export';

    case TasksView = 'tasks.view';
    case TasksCreate = 'tasks.create';
    case TasksUpdate = 'tasks.update';
    case TasksDelete = 'tasks.delete';
    case TasksComplete = 'tasks.complete';

    case ActivitiesView = 'activities.view';
    case ActivitiesCreate = 'activities.create';
    case ActivitiesUpdate = 'activities.update';
    case ActivitiesDelete = 'activities.delete';

    case AuditView = 'audit.view';
    case AuditExport = 'audit.export';

    case FeaturesView = 'features.view';

    public function module(): string
    {
        return explode('.', $this->value)[0];
    }

    public function action(): string
    {
        return explode('.', $this->value)[1];
    }

    public function label(): string
    {
        $action = ucfirst(str_replace('_', ' ', $this->action()));
        $module = ucfirst(str_replace('_', ' ', $this->module()));

        return "{$action} {$module}";
    }

    /**
     * @return array<string, list<self>>
     */
    public static function groupedByModule(): array
    {
        $grouped = [];

        foreach (self::cases() as $case) {
            $grouped[$case->module()][] = $case;
        }

        return $grouped;
    }
}
