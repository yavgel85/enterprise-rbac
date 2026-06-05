<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $this->call([
            ModuleSeeder::class,
            PermissionSeeder::class,
            PermissionGroupSeeder::class,
            FeatureSeeder::class,
            SuperAdminUserSeeder::class,
            DemoTenantSeeder::class,
        ]);
    }
}
