<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $modules = Module::query()->pluck('id', 'slug');

        foreach (PermissionEnum::cases() as $permission) {
            $moduleSlug = $permission->module();
            $moduleId = $modules->get($moduleSlug);

            if ($moduleId === null) {
                continue;
            }

            Permission::updateOrCreate(
                ['slug' => $permission->value],
                [
                    'module_id' => $moduleId,
                    'name' => $permission->label(),
                    'description' => null,
                ]
            );
        }
    }
}
