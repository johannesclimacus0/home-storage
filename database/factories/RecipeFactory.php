<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recipe>
 */
class RecipeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'created_by_user_id' => User::factory(),
            'title' => fake()->words(3, true),
            'description' => fake()->optional()->paragraph(),
            'servings' => fake()->numberBetween(1, 8),
            'before_cooking_minutes' => fake()->numberBetween(0, 60),
            'cooking_minutes' => fake()->numberBetween(0, 180),
        ];
    }

    public function system(): static
    {
        return $this->state(fn (): array => [
            'created_by_user_id' => null,
        ]);
    }
}
