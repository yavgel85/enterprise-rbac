<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Module;
use App\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Permission>
 */
class PermissionFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $action = fake()->randomElement(['view', 'create', 'update', 'delete']);
        $resource = fake()->unique()->word();

        return [
            'module_id' => Module::factory(),
            'name' => ucfirst($action).' '.ucfirst($resource),
            'slug' => "{$resource}.{$action}",
            'description' => null,
        ];
    }
}
