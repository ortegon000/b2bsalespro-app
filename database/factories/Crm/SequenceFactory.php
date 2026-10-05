<?php

namespace Database\Factories\Crm;

use App\Domain\Crm\Models\Sequence;
use App\Domain\Crm\Models\SequenceStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Sequence>
 */
class SequenceFactory extends Factory
{
    protected $model = Sequence::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return ['name' => 'Refuerzo '.fake()->unique()->word()];
    }

    /**
     * Crea los pasos de los días 1..$days; con plantilla de Brevo (1000 + día) por defecto.
     */
    public function withSteps(int $days = 30, bool $withTemplates = true): static
    {
        return $this->afterCreating(function (Sequence $sequence) use ($days, $withTemplates): void {
            foreach (range(1, $days) as $day) {
                SequenceStep::factory()->for($sequence)->create([
                    'day' => $day,
                    'brevo_template_id' => $withTemplates ? 1000 + $day : null,
                ]);
            }
        });
    }
}
