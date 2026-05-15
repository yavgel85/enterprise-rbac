<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\DealStage;
use App\Enums\DealStatus;
use App\Models\Deal;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Deal>
 */
class DealFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'company_id' => null,
            'contact_id' => null,
            'department_id' => null,
            'title' => fake()->catchPhrase(),
            'amount' => fake()->randomFloat(2, 100, 100000),
            'currency' => 'USD',
            'stage' => fake()->randomElement(DealStage::cases())->value,
            'probability' => fake()->numberBetween(0, 100),
            'expected_close_date' => fake()->dateTimeBetween('now', '+90 days')->format('Y-m-d'),
            'closed_at' => null,
            'owner_id' => null,
            'created_by' => null,
            'status' => DealStatus::Draft->value,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => ['status' => DealStatus::Active->value]);
    }

    public function won(): static
    {
        return $this->state(fn () => [
            'stage' => DealStage::Won->value,
            'status' => DealStatus::Closed->value,
            'closed_at' => now(),
        ]);
    }
}
