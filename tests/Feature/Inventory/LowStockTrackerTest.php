<?php

namespace Tests\Feature\Inventory;

use App\Events\Inventory\ProductBecameLowStock;
use App\Events\Inventory\ProductRecoveredFromLowStock;
use App\Models\Household;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StorageLocation;
use App\Services\Inventory\LowStockTracker;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class LowStockTrackerTest extends TestCase
{
    use RefreshDatabase;

    public function test_tracker_reports_low_stock_state_transitions(): void
    {
        Event::fake([
            ProductBecameLowStock::class,
            ProductRecoveredFromLowStock::class,
        ]);
        $household = Household::factory()->create();
        $product = Product::factory()->mass()->create();

        $householdProduct = HouseholdProduct::factory()
            ->for($household)
            ->for($product)
            ->create([
                'low_stock_threshold' => '100.000',
                'low_stock_since' => null,
            ]);

        $location = StorageLocation::factory()
            ->for($household)
            ->create();

        $stock = Stock::factory()
            ->for($householdProduct)
            ->for($location, 'storageLocation')
            ->create([
                'quantity' => '150.000',
            ]);

        $tracker = app(LowStockTracker::class);

        $firstDetectedAt = CarbonImmutable::parse('2026-08-21 10:00:00');
        $later = $firstDetectedAt->addHour();

        $normalResult = $tracker->refresh(
            $householdProduct->refresh(),
            $firstDetectedAt,
        );

        Event::assertNotDispatched(ProductBecameLowStock::class);
        Event::assertNotDispatched(ProductRecoveredFromLowStock::class);

        $this->assertSame('150.000', $normalResult->totalQuantity);
        $this->assertFalse($normalResult->becameLowStock);
        $this->assertFalse($normalResult->recovered);

        $stock->updateOrFail(['quantity' => '90.000']);

        $firstLowStockResult = $tracker->refresh(
            $householdProduct->refresh(),
            $firstDetectedAt,
        );

        Event::assertDispatched(
            ProductBecameLowStock::class,
            function (ProductBecameLowStock $event) use ($householdProduct, $firstDetectedAt): bool {
                return $event->householdProductId === $householdProduct->id
                    && $event->totalQuantity === '90.000'
                    && $event->occurredAt->equalTo($firstDetectedAt);
            },
        );
        Event::assertNotDispatched(ProductRecoveredFromLowStock::class);

        $this->assertSame('90.000', $firstLowStockResult->totalQuantity);
        $this->assertTrue($firstLowStockResult->becameLowStock);
        $this->assertFalse($firstLowStockResult->recovered);

        $stillLowStockResult = $tracker->refresh(
            $householdProduct->refresh(),
            $later,
        );
        Event::assertDispatchedTimes(ProductBecameLowStock::class, 1);
        Event::assertNotDispatched(ProductRecoveredFromLowStock::class);

        $this->assertFalse($stillLowStockResult->becameLowStock);
        $this->assertFalse($stillLowStockResult->recovered);
        $this->assertTrue(
            $householdProduct->refresh()->low_stock_since->equalTo($firstDetectedAt),
        );

        $stock->updateOrFail(['quantity' => '150.000']);

        $recoveredResult = $tracker->refresh(
            $householdProduct->refresh(),
            $later,
        );

        Event::assertDispatchedTimes(ProductBecameLowStock::class, 1);
        Event::assertDispatched(
            ProductRecoveredFromLowStock::class,
            function (ProductRecoveredFromLowStock $event) use ($householdProduct, $later): bool {
                return $event->householdProductId === $householdProduct->id
                    && $event->totalQuantity === '150.000'
                    && $event->occurredAt->equalTo($later);
            },
        );
        Event::assertDispatchedTimes(ProductRecoveredFromLowStock::class, 1);

        $this->assertSame('150.000', $recoveredResult->totalQuantity);
        $this->assertFalse($recoveredResult->becameLowStock);
        $this->assertTrue($recoveredResult->recovered);
        $this->assertNull($householdProduct->refresh()->low_stock_since);
    }
}
