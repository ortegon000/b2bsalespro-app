<?php

namespace Database\Factories\Crm;

use App\Domain\Crm\Enums\TeamRole;
use App\Domain\Crm\Models\TeamMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TeamMember>
 */
class TeamMemberFactory extends Factory
{
    protected $model = TeamMember::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role' => TeamRole::Seller,
        ];
    }

    public function admin(): static
    {
        return $this->state(['role' => TeamRole::Admin]);
    }
}
