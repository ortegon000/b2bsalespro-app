<?php

namespace Database\Factories\Crm;

use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => fake()->sentence(4),
            'due_on' => Task::today(),
        ];
    }

    public function completed(): static
    {
        return $this->state(['completed_at' => now()]);
    }
}
