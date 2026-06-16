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
    case UsersUnlock = 'users.unlock';
    case UsersSetPassword = 'users.set-password';

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
    case AuditManage = 'audit.manage';

    case ApprovalsView = 'approvals.view';

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

    /**
     * Distinct module slugs that own at least one permission.
     *
     * @return list<string>
     */
    public static function modules(): array
    {
        return array_values(array_unique(array_map(
            fn (self $case) => $case->module(),
            self::cases()
        )));
    }

    /**
     * The wildcard slug ("module.*") for every module.
     *
     * @return list<string>
     */
    public static function wildcardSlugs(): array
    {
        return array_map(fn (string $module) => "{$module}.*", self::modules());
    }

    public static function isWildcard(string $slug): bool
    {
        return str_ends_with($slug, '.*');
    }

    /**
     * Replace any "module.*" wildcard slug with the concrete permission slugs
     * of that module. Unknown wildcards (modules without permissions) are dropped.
     *
     * @param  iterable<string>  $slugs
     * @return list<string>
     */
    public static function expandWildcards(iterable $slugs): array
    {
        $modules = [];
        $concrete = [];

        foreach ($slugs as $slug) {
            if (self::isWildcard($slug)) {
                $modules[substr($slug, 0, -2)] = true;
            } else {
                $concrete[] = $slug;
            }
        }

        if ($modules !== []) {
            foreach (self::cases() as $case) {
                if (isset($modules[$case->module()])) {
                    $concrete[] = $case->value;
                }
            }
        }

        return array_values(array_unique($concrete));
    }
}
