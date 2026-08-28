<?php

namespace Database\Factories;

use App\Models\Household;
use App\Models\Product;
use App\Models\ShoppingListItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ShoppingListItem>
 */
class ShoppingListItemFactory extends Factory
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
            'product_id' => Product::factory(),
            'added_by_user_id' => User::factory(), // for($user, 'addedBy')
            'quantity' => $this->faker->numberBetween(1, 15),
            'completed_at' => null,
        ];
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'completed_at' => now(),
        ]);
    }
}
