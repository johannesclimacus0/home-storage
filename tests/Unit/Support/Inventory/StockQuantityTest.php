<?php

namespace Tests\Unit\Support\Inventory;

use App\Enums\MeasurementType;
use App\Enums\MeasurementUnit;
use App\Exceptions\Inventory\InvalidStockQuantity;
use App\Support\Inventory\StockQuantity;
use Tests\TestCase;

final class StockQuantityTest extends TestCase
{
    public function test_it_normalizes_base_and_large_units(): void
    {
        $this->assertSame(
            '250.000',
            StockQuantity::toBaseUnit('250', MeasurementUnit::Gram, MeasurementType::Mass)
        );
        $this->assertSame(
            '1500.000',
            StockQuantity::toBaseUnit('1.5', MeasurementUnit::Kilogram, MeasurementType::Mass)
        );
        $this->assertSame(
            '750.000',
            StockQuantity::toBaseUnit('0.75', MeasurementUnit::Liter, MeasurementType::Volume)
        );
    }

    public function test_it_normalizes_database_values_and_minor_units(): void
    {
        $this->assertSame('12.300', StockQuantity::databaseValue('0012.3'));
        $this->assertSame(12300, StockQuantity::toMinorUnits('12.3'));
        $this->assertSame('12.300', StockQuantity::fromMinorUnits(12300));
    }

    public function test_count_quantity_must_be_whole(): void
    {
        $this->expectException(InvalidStockQuantity::class);

        StockQuantity::toBaseUnit('1.5', MeasurementUnit::Piece, MeasurementType::Count);
    }

    public function test_unit_must_match_measurement_type(): void
    {
        $this->expectException(InvalidStockQuantity::class);

        StockQuantity::toBaseUnit('1', MeasurementUnit::Liter, MeasurementType::Mass);
    }

    public function test_invalid_negative_precise_and_oversized_quantities_are_rejected(): void
    {
        foreach (['-1', '1.2345', '1e3', '100000000000'] as $quantity) {
            try {
                StockQuantity::toBaseUnit($quantity, MeasurementUnit::Gram, MeasurementType::Mass);
                $this->fail("Quantity {$quantity} was accepted.");
            } catch (InvalidStockQuantity) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
