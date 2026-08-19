<?php

namespace Tests\Feature\Inventory;

use App\Enums\MeasurementType;
use App\Models\Household;
use App\Models\HouseholdProduct;
use App\Models\Stock;
use App\Models\StorageLocation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryFactoriesTest extends TestCase
{
    use RefreshDatabase;

    public function test_stock_factory_creates_a_consistent_inventory_graph(): void
    {
        $household = Household::factory()->create();
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
        ]);
        $location = StorageLocation::factory()->create([
            'household_id' => $household->id,
        ]);

        $stock = Stock::factory()->create([
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $location->id,
            'quantity' => '1500.000',
        ]);

        $product = $stock->householdProduct->product;

        $this->assertSame($household->id, $stock->householdProduct->household_id);
        $this->assertSame($household->id, $stock->storageLocation->household_id);
        $this->assertNotEmpty($household->uuid);
        $this->assertNotEmpty($stock->storageLocation->uuid);
        $this->assertNotEmpty($product->uuid);
        $this->assertInstanceOf(MeasurementType::class, $product->measurement_type);
        $this->assertSame('1500.000', $stock->quantity);
        $this->assertTrue($stock->householdProduct->stocks->contains($stock));
        $this->assertTrue($stock->storageLocation->stocks->contains($stock));
    }
}
