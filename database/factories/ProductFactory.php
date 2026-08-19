<?php

namespace Database\Factories;

use App\Enums\MeasurementType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->unique()->words(2, true),
            'measurement_type' => fake()->randomElement(MeasurementType::cases()),
        ];
    }

    public function mass(): static
    {
        return $this->state(fn (): array => [
            'measurement_type' => MeasurementType::Mass,
        ]);
    }

    public function volume(): static
    {
        return $this->state(fn (): array => [
            'measurement_type' => MeasurementType::Volume,
        ]);
    }

    public function counted(): static
    {
        return $this->state(fn (): array => [
            'measurement_type' => MeasurementType::Count,
        ]);
    }
}
