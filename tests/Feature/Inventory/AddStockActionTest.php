<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\AddStockAction;
use App\DTO\Inventory\AddStockData;
use App\Enums\HouseholdRole;
use App\Enums\MeasurementUnit;
use App\Exceptions\Inventory\InvalidStockQuantity;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddStockActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_create_stock_using_a_larger_unit(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph('volume');

        $result = $this->action()->handle(new AddStockData(
            householdUuid: $household->uuid,
            actorUserId: $member->id,
            productUuid: $householdProduct->product->uuid,
            storageLocationUuid: $location->uuid,
            quantity: '0.5',
            unit: MeasurementUnit::Liter,
        ));

        $this->assertSame('500.000', $result->addedQuantity);
        $this->assertSame(MeasurementUnit::Milliliter, $result->unit);
        $this->assertSame('500.000', $result->locationQuantity);
        $this->assertSame('500.000', $result->totalQuantity);
        $this->assertDatabaseHas('stocks', [
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $location->id,
            'quantity' => '500.000',
        ]);
    }

    public function test_existing_stock_is_increased_and_total_includes_other_locations(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph('mass');
        Stock::factory()->create([
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $location->id,
            'quantity' => '100.000',
        ]);
        $otherLocation = StorageLocation::factory()->create(['household_id' => $household->id]);
        Stock::factory()->create([
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $otherLocation->id,
            'quantity' => '25.000',
        ]);

        $result = $this->action()->handle(new AddStockData(
            householdUuid: $household->uuid,
            actorUserId: $member->id,
            productUuid: $householdProduct->product->uuid,
            storageLocationUuid: $location->uuid,
            quantity: '350',
            unit: MeasurementUnit::Gram,
        ));

        $this->assertSame('450.000', $result->locationQuantity);
        $this->assertSame('475.000', $result->totalQuantity);
        $this->assertDatabaseCount('stocks', 2);
    }

    public function test_zero_quantity_succeeds_without_creating_stock(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph('count');

        $result = $this->action()->handle(new AddStockData(
            householdUuid: $household->uuid,
            actorUserId: $member->id,
            productUuid: $householdProduct->product->uuid,
            storageLocationUuid: $location->uuid,
            quantity: '0',
            unit: MeasurementUnit::Piece,
        ));

        $this->assertSame('0.000', $result->locationQuantity);
        $this->assertSame('0.000', $result->totalQuantity);
        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_negative_quantity_is_rejected(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph('mass');

        $this->assertThrows(
            fn () => $this->action()->handle(new AddStockData(
                householdUuid: $household->uuid,
                actorUserId: $member->id,
                productUuid: $householdProduct->product->uuid,
                storageLocationUuid: $location->uuid,
                quantity: '-1',
                unit: MeasurementUnit::Gram,
            )),
            InvalidStockQuantity::class,
            'The quantity cannot be negative.',
        );

        $this->assertDatabaseCount('stocks', 0);
    }

    public function test_incompatible_unit_is_rejected(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph('count');

        $this->assertThrows(
            fn () => $this->action()->handle(new AddStockData(
                householdUuid: $household->uuid,
                actorUserId: $member->id,
                productUuid: $householdProduct->product->uuid,
                storageLocationUuid: $location->uuid,
                quantity: '2',
                unit: MeasurementUnit::Liter,
            )),
            InvalidStockQuantity::class,
            'The unit is not compatible with the product.',
        );
    }

    public function test_location_from_another_household_cannot_be_used(): void
    {
        [$household, $member, $householdProduct] = $this->inventoryGraph('mass');
        $foreignLocation = StorageLocation::factory()->create();

        $this->assertThrows(
            fn () => $this->action()->handle(new AddStockData(
                householdUuid: $household->uuid,
                actorUserId: $member->id,
                productUuid: $householdProduct->product->uuid,
                storageLocationUuid: $foreignLocation->uuid,
                quantity: '1',
                unit: MeasurementUnit::Gram,
            )),
            ModelNotFoundException::class,
        );
    }

    public function test_outsider_cannot_add_stock(): void
    {
        [$household, , $householdProduct, $location] = $this->inventoryGraph('mass');
        $outsider = User::factory()->create();

        $this->assertThrows(
            fn () => $this->action()->handle(new AddStockData(
                householdUuid: $household->uuid,
                actorUserId: $outsider->id,
                productUuid: $householdProduct->product->uuid,
                storageLocationUuid: $location->uuid,
                quantity: '1',
                unit: MeasurementUnit::Gram,
            )),
            ModelNotFoundException::class,
        );
    }

    private function action(): AddStockAction
    {
        return $this->app->make(AddStockAction::class);
    }

    private function inventoryGraph(string $type): array
    {
        $household = Household::factory()->create();
        $member = User::factory()->create();
        HouseholdMembership::factory()->create([
            'household_id' => $household->id,
            'user_id' => $member->id,
            'role' => HouseholdRole::Member,
        ]);
        $productFactory = Product::factory();
        $product = match ($type) {
            'mass' => $productFactory->mass()->create(),
            'volume' => $productFactory->volume()->create(),
            'count' => $productFactory->counted()->create(),
        };
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
            'product_id' => $product->id,
        ]);
        $location = StorageLocation::factory()->create(['household_id' => $household->id]);

        return [$household, $member, $householdProduct, $location];
    }
}
