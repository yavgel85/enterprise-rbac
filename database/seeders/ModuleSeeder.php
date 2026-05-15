<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $modules = [
            ['slug' => 'system', 'name' => 'System', 'description' => 'Audit, features, system-level resources', 'sort_order' => 0],
            ['slug' => 'users', 'name' => 'Users', 'description' => 'User management', 'sort_order' => 10],
            ['slug' => 'roles', 'name' => 'Roles', 'description' => 'Role management', 'sort_order' => 20],
            ['slug' => 'permissions', 'name' => 'Permissions', 'description' => 'Permission management', 'sort_order' => 30],
            ['slug' => 'departments', 'name' => 'Departments', 'description' => 'Department management', 'sort_order' => 40],
            ['slug' => 'companies', 'name' => 'Companies', 'description' => 'CRM company records', 'sort_order' => 50],
            ['slug' => 'contacts', 'name' => 'Contacts', 'description' => 'CRM contact records', 'sort_order' => 60],
            ['slug' => 'deals', 'name' => 'Deals', 'description' => 'CRM sales pipeline', 'sort_order' => 70],
            ['slug' => 'tasks', 'name' => 'Tasks', 'description' => 'Task tracking', 'sort_order' => 80],
            ['slug' => 'activities', 'name' => 'Activities', 'description' => 'Calls, meetings, emails, notes', 'sort_order' => 90],
            ['slug' => 'audit', 'name' => 'Audit', 'description' => 'Audit log access', 'sort_order' => 100],
            ['slug' => 'features', 'name' => 'Features', 'description' => 'Feature flag visibility', 'sort_order' => 110],
        ];

        foreach ($modules as $module) {
            Module::updateOrCreate(['slug' => $module['slug']], $module);
        }
    }
}
