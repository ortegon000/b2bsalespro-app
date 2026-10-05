<?php

namespace Database\Factories\Crm;

use App\Domain\Crm\Models\Sequence;
use App\Domain\Crm\Models\SequenceStep;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SequenceStep>
 */
class SequenceStepFactory extends Factory
{
    protected $model = SequenceStep::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'sequence_id' => Sequence::factory(),
            'day' => fake()->numberBetween(1, 30),
            'brevo_template_id' => fake()->numberBetween(1, 500),
        ];
    }
}
