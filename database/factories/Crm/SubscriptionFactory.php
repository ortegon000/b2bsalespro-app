<?php

namespace Database\Factories\Crm;

use App\Domain\Crm\Enums\SubscriptionStatus;
use App\Domain\Crm\Models\Contact;
use App\Domain\Crm\Models\Course;
use App\Domain\Crm\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'contact_id' => Contact::factory(),
            'course_id' => Course::factory(),
            'status' => SubscriptionStatus::Active,
        ];
    }
}
