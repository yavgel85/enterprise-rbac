<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\PermissionGroup;
use Illuminate\Database\Seeder;

class PermissionGroupSeeder extends Seeder
{
    /**
     * Global (tenant_id = null) permission bundles that make day-to-day role
     * editing faster. Slugs reference concrete permissions only.
     */
    public function run(): void
    {
        $groups = [
            [
                'slug' => 'crm-read-only',
                'name' => 'CRM — read only',
                'description' => 'View access across companies, contacts, deals, tasks and activities.',
                'permissions' => [
                    'companies.view', 'contacts.view', 'deals.view',
                    'tasks.view', 'activities.view',
                ],
            ],
            [
                'slug' => 'crm-full',
                'name' => 'CRM — full operations',
                'description' => 'Create/update across the whole CRM (no destructive deletes).',
                'permissions' => [
                    'companies.view', 'companies.create', 'companies.update',
                    'contacts.view', 'contacts.create', 'contacts.update',
                    'deals.view', 'deals.create', 'deals.update',
                    'tasks.view', 'tasks.create', 'tasks.update', 'tasks.complete',
                    'activities.view', 'activities.create', 'activities.update',
                ],
            ],
            [
                'slug' => 'user-administration',
                'name' => 'User administration',
                'description' => 'Manage users, roles and permissions within the tenant.',
                'permissions' => [
                    'users.view', 'users.create', 'users.update', 'users.invite',
                    'users.unlock', 'users.set-password',
                    'roles.view', 'roles.assign',
                    'permissions.view', 'permissions.assign',
                ],
            ],
            [
                'slug' => 'audit-access',
                'name' => 'Audit access',
                'description' => 'Read and export the audit trail.',
                'permissions' => ['audit.view', 'audit.export'],
            ],
        ];

        $permissionIds = Permission::query()->pluck('id', 'slug');

        foreach ($groups as $group) {
            $model = PermissionGroup::updateOrCreate(
                ['tenant_id' => null, 'slug' => $group['slug']],
                ['name' => $group['name'], 'description' => $group['description']],
            );

            $ids = collect($group['permissions'])
                ->map(fn (string $slug) => $permissionIds->get($slug))
                ->filter()
                ->values()
                ->all();

            $model->permissions()->sync($ids);
        }
    }
}
