<?php

namespace Database\Factories\Crm;

use App\Domain\Crm\Enums\StageType;
use App\Domain\Crm\Models\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stage>
 */
class StageFactory extends Factory
{
    protected $model = Stage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'slug' => fake()->unique()->slug(2),
            'type' => StageType::Open,
            'position' => fake()->unique()->numberBetween(1, 1000),
        ];
    }

    public function won(): static
    {
        return $this->state(['type' => StageType::Won]);
    }

    public function lost(): static
    {
        return $this->state(['type' => StageType::Lost]);
    }
}
