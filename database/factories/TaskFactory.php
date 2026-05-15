<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\TaskPriority;
use App\Enums\TaskStatus;
use App\Models\Task;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'taskable_type' => null,
            'taskable_id' => null,
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'due_date' => fake()->dateTimeBetween('now', '+30 days'),
            'priority' => fake()->randomElement(TaskPriority::cases())->value,
            'status' => TaskStatus::Open->value,
            'assignee_id' => null,
            'created_by' => null,
            'completed_at' => null,
        ];
    }

    public function done(): static
    {
        return $this->state(fn () => [
            'status' => TaskStatus::Done->value,
            'completed_at' => now(),
        ]);
    }
}
