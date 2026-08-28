<?php

namespace Database\Factories;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HouseholdMembership>
 */
class HouseholdMembershipFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'user_id' => User::factory(),
            'role' => HouseholdRole::Member,
            'low_stock_reminders_enabled' => true,
            'low_stock_reminder_interval_hours' => 24,
        ];
    }

    public function owner(): static
    {
        return $this->state(fn (): array => [
            'role' => HouseholdRole::Owner,
        ]);
    }
}
