<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\ActivityType;
use App\Models\Activity;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Activity>
 */
class ActivityFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'type' => fake()->randomElement(ActivityType::cases())->value,
            'subject' => fake()->sentence(4),
            'body' => fake()->paragraph(),
            'subjectable_type' => null,
            'subjectable_id' => null,
            'happened_at' => fake()->dateTimeBetween('-30 days', 'now'),
            'user_id' => null,
        ];
    }
}
