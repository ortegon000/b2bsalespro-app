<?php

namespace Database\Factories\Crm;

use App\Domain\Crm\Enums\CourseModality;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Course;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Course>
 */
class CourseFactory extends Factory
{
    protected $model = Course::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'title' => 'Curso de ventas '.fake()->word(),
            'modality' => fake()->randomElement(CourseModality::cases()),
            'hours' => fake()->randomElement([8, 12, 16]),
            'starts_on' => now()->subDays(10)->toDateString(),
            'ends_on' => now()->subDays(8)->toDateString(),
        ];
    }

    public function delivered(): static
    {
        return $this->state(['delivered_at' => now()]);
    }
}
