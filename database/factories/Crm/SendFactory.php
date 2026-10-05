<?php

namespace Database\Factories\Crm;

use App\Domain\Crm\Enums\SendStatus;
use App\Domain\Crm\Models\Send;
use App\Domain\Crm\Models\SequenceStep;
use App\Domain\Crm\Models\Subscription;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Send>
 */
class SendFactory extends Factory
{
    protected $model = Send::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subscription_id' => Subscription::factory(),
            'sequence_step_id' => SequenceStep::factory(),
            'scheduled_for' => now(),
            'status' => SendStatus::Pending,
        ];
    }

    public function status(SendStatus $status): static
    {
        return $this->state(['status' => $status]);
    }
}
