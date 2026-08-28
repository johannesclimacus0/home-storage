<?php

namespace Database\Factories;

use App\Models\Household;
use App\Models\HouseholdMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HouseholdMessage>
 */
class HouseholdMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'household_id' => Household::factory(),
            'sender_id' => User::factory(),
            'content' => $this->faker->text(),
            'edited_at' => null,
            'deleted_at' => null,
        ];
    }

    public function edited(): static
    {
        return $this->state(fn() => [
            'edited_at' => now(),
        ]);
    }

    public function deleted(): static
    {
        return $this->state(fn() => [
            'deleted_at' => now(),
        ]);
    }
}
