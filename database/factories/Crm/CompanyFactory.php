<?php

namespace Database\Factories\Crm;

use App\Domain\Crm\Enums\LeadSource;
use App\Domain\Crm\Models\Company;
use App\Domain\Crm\Models\Stage;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Company>
 */
class CompanyFactory extends Factory
{
    protected $model = Company::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'industry' => fake()->randomElement(['Retail', 'Manufactura', 'Servicios', 'Tecnología']),
            'website' => fake()->url(),
            'stage_id' => Stage::factory(),
            'source' => fake()->randomElement(LeadSource::cases()),
            'stage_changed_at' => now(),
        ];
    }
}
