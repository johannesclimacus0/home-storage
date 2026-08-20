<?php

namespace Tests\Unit\Models;

use App\Enums\MeasurementType;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StorageLocation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class InventoryModelsTest extends TestCase
{
    public function test_product_configuration_and_relationships(): void
    {
        $product = new Product;
        $product->setRawAttributes(['measurement_type' => 'mass']);

        $this->assertSame(['name', 'measurement_type'], $product->getFillable());
        $this->assertSame(MeasurementType::Mass, $product->measurement_type);
        $this->assertSame('uuid', $product->getRouteKeyName());
        $this->assertInstanceOf(HasMany::class, $product->householdProducts());
    }

    public function test_household_product_configuration_and_relationships(): void
    {
        $householdProduct = new HouseholdProduct;
        $householdProduct->setRawAttributes(['low_stock_threshold' => '1500']);
        $householdProduct->low_stock_since = '2026-08-20 10:00:00';

        $this->assertSame(
            ['household_id', 'product_id', 'low_stock_threshold', 'low_stock_since'],
            $householdProduct->getFillable(),
        );
        $this->assertSame('1500.000', $householdProduct->low_stock_threshold);
        $this->assertInstanceOf(BelongsTo::class, $householdProduct->household());
        $this->assertInstanceOf(BelongsTo::class, $householdProduct->product());
        $this->assertInstanceOf(HasMany::class, $householdProduct->stocks());
        $this->assertInstanceOf(CarbonImmutable::class, $householdProduct->low_stock_since);
    }

    public function test_storage_location_configuration_and_relationships(): void
    {
        $location = new StorageLocation;

        $this->assertSame(['household_id', 'name'], $location->getFillable());
        $this->assertSame('uuid', $location->getRouteKeyName());
        $this->assertInstanceOf(BelongsTo::class, $location->household());
        $this->assertInstanceOf(HasMany::class, $location->stocks());
    }

    public function test_stock_configuration_and_relationships(): void
    {
        $stock = new Stock;
        $stock->setRawAttributes(['quantity' => '350']);

        $this->assertSame(
            ['household_product_id', 'storage_location_id', 'quantity'],
            $stock->getFillable(),
        );
        $this->assertSame('350.000', $stock->quantity);
        $this->assertInstanceOf(BelongsTo::class, $stock->householdProduct());
        $this->assertInstanceOf(BelongsTo::class, $stock->storageLocation());
    }
}
