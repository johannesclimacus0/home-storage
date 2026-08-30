<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RecipeIngredient>
 */
class RecipeIngredientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'product_id' => Product::factory(),
            'quantity' => fake()->numberBetween(1, 5000),
            'position' => 1,
            'is_optional' => false,
            'note' => fake()->optional()->sentence(),
        ];
    }

    public function optional(): static
    {
        return $this->state(fn (): array => [
            'is_optional' => true,
        ]);
    }
}
