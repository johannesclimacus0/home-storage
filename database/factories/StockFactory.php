<?php

namespace Database\Factories;

use App\Models\HouseholdProduct;
use App\Models\Stock;
use App\Models\StorageLocation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'household_product_id' => HouseholdProduct::factory(),
            'storage_location_id' => StorageLocation::factory(),
            'quantity' => fake()->randomFloat(3, 0, 10000),
        ];
    }
}
