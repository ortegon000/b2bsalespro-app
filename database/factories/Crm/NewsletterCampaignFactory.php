<?php

namespace Database\Factories\Crm;

use App\Domain\Crm\Models\NewsletterCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterCampaign>
 */
class NewsletterCampaignFactory extends Factory
{
    protected $model = NewsletterCampaign::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Newsletter '.fake()->monthName(),
            'brevo_template_id' => fake()->numberBetween(1, 500),
            'brevo_campaign_id' => fake()->unique()->numberBetween(1, 5000),
        ];
    }
}
