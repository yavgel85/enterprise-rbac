<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Role;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
class RoleFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'tenant_id' => Tenant::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(1, 99999),
            'description' => null,
            'is_system' => false,
            'level' => fake()->numberBetween(10, 80),
        ];
    }

    public function system(): static
    {
        return $this->state(fn () => [
            'tenant_id' => null,
            'is_system' => true,
        ]);
    }

    public function withLevel(int $level): static
    {
        return $this->state(fn () => ['level' => $level]);
    }
}
