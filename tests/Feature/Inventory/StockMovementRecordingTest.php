<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\AddStockAction;
use App\Actions\Inventory\ConsumeStockAction;
use App\Contracts\Inventory\StockMovementRepository;
use App\DTO\Inventory\AddStockData;
use App\DTO\Inventory\ConsumeStockData;
use App\Enums\HouseholdRole;
use App\Enums\MeasurementUnit;
use App\Enums\StockMovementType;
use App\Exceptions\Inventory\InsufficientStock;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use RuntimeException;
use Tests\TestCase;

final class StockMovementRecordingTest extends TestCase
{
    use RefreshDatabase;

    public function test_adding_stock_records_purchase_in_original_and_base_units(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph('volume');

        $this->addStock($household, $member, $householdProduct, $location, '0.5', MeasurementUnit::Liter);

        $movement = StockMovement::query()->sole();

        $this->assertSame(StockMovementType::Purchase, $movement->type);
        $this->assertSame(MeasurementUnit::Liter, $movement->input_unit);
        $this->assertSame('0.500', $movement->input_quantity);
        $this->assertSame('500.000', $movement->quantity_delta);
        $this->assertSame('0.000', $movement->quantity_before);
        $this->assertSame('500.000', $movement->quantity_after);
        $this->assertSame($householdProduct->product->name, $movement->product_name);
        $this->assertSame($location->name, $movement->storage_location_name);
        $this->assertSame($member->name, $movement->actor_name);
    }

    public function test_adding_to_existing_stock_records_correct_before_and_after(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph('mass', '100.000');

        $this->addStock($household, $member, $householdProduct, $location, '350', MeasurementUnit::Gram);

        $movement = StockMovement::query()->sole();

        $this->assertSame('100.000', $movement->quantity_before);
        $this->assertSame('350.000', $movement->quantity_delta);
        $this->assertSame('450.000', $movement->quantity_after);
    }

    public function test_consuming_stock_records_negative_movement(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph('volume', '2000.000');

        $this->consumeStock($household, $member, $householdProduct, $location, '0.5', MeasurementUnit::Liter);

        $movement = StockMovement::query()->sole();

        $this->assertSame(StockMovementType::Consumption, $movement->type);
        $this->assertSame(MeasurementUnit::Liter, $movement->input_unit);
        $this->assertSame('0.500', $movement->input_quantity);
        $this->assertSame('-500.000', $movement->quantity_delta);
        $this->assertSame('2000.000', $movement->quantity_before);
        $this->assertSame('1500.000', $movement->quantity_after);
    }

    public function test_zero_quantity_does_not_record_movement(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph('count', '2.000');

        $this->addStock($household, $member, $householdProduct, $location, '0', MeasurementUnit::Piece);
        $this->consumeStock($household, $member, $householdProduct, $location, '0', MeasurementUnit::Piece);

        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame('2.000', Stock::query()->sole()->quantity);
    }

    public function test_failed_consumption_does_not_record_movement(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph('mass', '100.000');

        $this->assertThrows(
            fn () => $this->consumeStock(
                $household,
                $member,
                $householdProduct,
                $location,
                '101',
                MeasurementUnit::Gram,
            ),
            InsufficientStock::class,
        );

        $this->assertDatabaseCount('stock_movements', 0);
        $this->assertSame('100.000', Stock::query()->sole()->quantity);
    }

    public function test_movement_failure_rolls_back_stock_change(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph('mass', '100.000');

        $repository = Mockery::mock(StockMovementRepository::class);
        $repository->shouldReceive('create')
            ->once()
            ->andThrow(new RuntimeException('Movement storage failed.'));
        $this->app->instance(StockMovementRepository::class, $repository);

        $this->assertThrows(
            fn () => $this->addStock(
                $household,
                $member,
                $householdProduct,
                $location,
                '50',
                MeasurementUnit::Gram,
            ),
            RuntimeException::class,
        );

        $this->assertSame('100.000', Stock::query()->sole()->quantity);
        $this->assertDatabaseCount('stock_movements', 0);
    }

    public function test_movement_survives_storage_location_deletion_with_snapshot(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph('mass');
        $locationName = $location->name;

        $this->addStock($household, $member, $householdProduct, $location, '50', MeasurementUnit::Gram);
        $location->deleteOrFail();

        $movement = StockMovement::query()->sole();

        $this->assertNull($movement->storage_location_id);
        $this->assertSame($locationName, $movement->storage_location_name);
    }

    private function addStock(
        Household $household,
        User $member,
        HouseholdProduct $householdProduct,
        StorageLocation $location,
        string $quantity,
        MeasurementUnit $unit,
    ): void {
        $this->app->make(AddStockAction::class)->handle(new AddStockData(
            householdUuid: $household->uuid,
            actorUserId: $member->getKey(),
            productUuid: $householdProduct->product->uuid,
            storageLocationUuid: $location->uuid,
            quantity: $quantity,
            unit: $unit,
        ));
    }

    private function consumeStock(
        Household $household,
        User $member,
        HouseholdProduct $householdProduct,
        StorageLocation $location,
        string $quantity,
        MeasurementUnit $unit,
    ): void {
        $this->app->make(ConsumeStockAction::class)->handle(new ConsumeStockData(
            householdUuid: $household->uuid,
            actorUserId: $member->getKey(),
            productUuid: $householdProduct->product->uuid,
            storageLocationUuid: $location->uuid,
            quantity: $quantity,
            unit: $unit,
        ));
    }

    private function inventoryGraph(string $type, ?string $quantity = null): array
    {
        $household = Household::factory()->create();
        $member = User::factory()->create();
        HouseholdMembership::factory()->create([
            'household_id' => $household->getKey(),
            'user_id' => $member->getKey(),
            'role' => HouseholdRole::Member,
        ]);

        $factory = Product::factory();
        $product = match ($type) {
            'mass' => $factory->mass()->create(),
            'volume' => $factory->volume()->create(),
            'count' => $factory->counted()->create(),
        };
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->getKey(),
            'product_id' => $product->getKey(),
        ]);
        $location = StorageLocation::factory()->create([
            'household_id' => $household->getKey(),
        ]);

        if ($quantity !== null) {
            Stock::factory()->create([
                'household_product_id' => $householdProduct->getKey(),
                'storage_location_id' => $location->getKey(),
                'quantity' => $quantity,
            ]);
        }

        return [$household, $member, $householdProduct, $location];
    }
}
