<?php

namespace Database\Factories;

use App\Enums\MeasurementUnit;
use App\Enums\StockMovementType;
use App\Models\Household;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
final class StockMovementFactory extends Factory
{
    public function definition(): array
    {
        return [
            'household_id' => Household::factory(),
            'product_id' => Product::factory()->mass(),
            'household_product_id' => fn (array $attributes) => HouseholdProduct::factory()->create([
                'household_id' => $attributes['household_id'],
                'product_id' => $attributes['product_id'],
            ])->getKey(),
            'storage_location_id' => fn (array $attributes) => StorageLocation::factory()->create([
                'household_id' => $attributes['household_id'],
            ])->getKey(),
            'actor_user_id' => User::factory(),
            'type' => StockMovementType::Purchase,
            'input_quantity' => '10.000',
            'input_unit' => MeasurementUnit::Gram,
            'quantity_delta' => '10.000',
            'quantity_before' => '0.000',
            'quantity_after' => '10.000',
            'product_name' => fn (array $attributes) => Product::query()->findOrFail($attributes['product_id'])->name,
            'storage_location_name' => fn (array $attributes) => StorageLocation::query()
                ->findOrFail($attributes['storage_location_id'])->name,
            'actor_name' => fn (array $attributes) => User::query()->findOrFail($attributes['actor_user_id'])->name,
        ];
    }
}
