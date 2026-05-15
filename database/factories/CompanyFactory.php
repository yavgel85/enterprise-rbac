<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\CompanyStatus;
use App\Models\Company;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->company(),
            'industry' => fake()->randomElement(['Software', 'Finance', 'Healthcare', 'Retail', 'Manufacturing']),
            'website' => fake()->url(),
            'phone' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'notes' => fake()->optional()->sentence(),
            'owner_id' => null,
            'created_by' => null,
            'status' => fake()->randomElement(CompanyStatus::cases())->value,
        ];
    }
}
