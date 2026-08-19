<?php

namespace Database\Factories;

use App\Models\Household;
use App\Models\HouseholdProduct;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HouseholdProduct>
 */
class HouseholdProductFactory extends Factory
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
            'low_stock_threshold' => fake()->randomFloat(3, 0, 5000),
        ];
    }
}
