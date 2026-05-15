<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $features = [
            ['slug' => 'advanced_analytics', 'name' => 'Advanced Analytics', 'description' => 'Advanced reporting dashboards', 'default_enabled' => false],
            ['slug' => 'audit_export', 'name' => 'Audit Export', 'description' => 'Export audit logs to CSV/JSON', 'default_enabled' => false],
            ['slug' => 'api_access', 'name' => 'API Access', 'description' => 'Public API for tenant integrations', 'default_enabled' => false],
            ['slug' => 'bulk_import', 'name' => 'Bulk Import', 'description' => 'Bulk import of CRM records via CSV', 'default_enabled' => false],
        ];

        foreach ($features as $feature) {
            Feature::updateOrCreate(['slug' => $feature['slug']], $feature);
        }
    }
}
