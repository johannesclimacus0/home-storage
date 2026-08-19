<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\ConsumeStockAction;
use App\DTO\Inventory\ConsumeStockData;
use App\Enums\HouseholdRole;
use App\Enums\MeasurementUnit;
use App\Exceptions\Inventory\InsufficientStock;
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

class ConsumeStockActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_consume_stock(): void
    {
        [$household, $member, $householdProduct, $location, $stock] = $this->inventoryGraph('mass', '1000');

        $result = $this->action()->handle($this->data(
            $household,
            $member,
            $householdProduct,
            $location,
            '350',
            MeasurementUnit::Gram,
        ));

        $this->assertSame('350.000', $result->consumedQuantity);
        $this->assertSame(MeasurementUnit::Gram, $result->unit);
        $this->assertSame('650.000', $result->locationQuantity);
        $this->assertSame('650.000', $result->totalQuantity);
        $this->assertSame('650.000', $stock->refresh()->quantity);
    }

    public function test_liters_are_converted_before_consuming(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph('volume', '2000');

        $result = $this->action()->handle($this->data(
            $household,
            $member,
            $householdProduct,
            $location,
            '0.5',
            MeasurementUnit::Liter,
        ));

        $this->assertSame('500.000', $result->consumedQuantity);
        $this->assertSame(MeasurementUnit::Milliliter, $result->unit);
        $this->assertSame('1500.000', $result->locationQuantity);
    }

    public function test_stock_cannot_become_negative(): void
    {
        [$household, $member, $householdProduct, $location, $stock] = $this->inventoryGraph('mass', '100');

        $this->assertThrows(
            fn () => $this->action()->handle($this->data(
                $household,
                $member,
                $householdProduct,
                $location,
                '100.001',
                MeasurementUnit::Gram,
            )),
            InsufficientStock::class,
            'Insufficient stock.',
        );

        $this->assertSame('100.000', $stock->refresh()->quantity);
    }

    public function test_zero_quantity_does_not_change_stock(): void
    {
        [$household, $member, $householdProduct, $location, $stock] = $this->inventoryGraph('count', '2');

        $result = $this->action()->handle($this->data(
            $household,
            $member,
            $householdProduct,
            $location,
            '0',
            MeasurementUnit::Piece,
        ));

        $this->assertSame('2.000', $result->locationQuantity);
        $this->assertSame('2.000', $stock->refresh()->quantity);
    }

    public function test_stock_must_exist_in_selected_location(): void
    {
        [$household, $member, $householdProduct] = $this->inventoryGraph('mass', '100');
        $emptyLocation = StorageLocation::factory()->create(['household_id' => $household->id]);

        $this->assertThrows(
            fn () => $this->action()->handle($this->data(
                $household,
                $member,
                $householdProduct,
                $emptyLocation,
                '1',
                MeasurementUnit::Gram,
            )),
            ModelNotFoundException::class,
        );
    }

    private function action(): ConsumeStockAction
    {
        return $this->app->make(ConsumeStockAction::class);
    }

    private function data(
        Household $household,
        User $member,
        HouseholdProduct $householdProduct,
        StorageLocation $location,
        string $quantity,
        MeasurementUnit $unit,
    ): ConsumeStockData {
        return new ConsumeStockData(
            householdUuid: $household->uuid,
            actorUserId: $member->id,
            productUuid: $householdProduct->product->uuid,
            storageLocationUuid: $location->uuid,
            quantity: $quantity,
            unit: $unit,
        );
    }

    private function inventoryGraph(string $type, string $quantity): array
    {
        $household = Household::factory()->create();
        $member = User::factory()->create();
        HouseholdMembership::factory()->create([
            'household_id' => $household->id,
            'user_id' => $member->id,
            'role' => HouseholdRole::Member,
        ]);
        $factory = Product::factory();
        $product = match ($type) {
            'mass' => $factory->mass()->create(),
            'volume' => $factory->volume()->create(),
            'count' => $factory->counted()->create(),
        };
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
            'product_id' => $product->id,
        ]);
        $location = StorageLocation::factory()->create(['household_id' => $household->id]);
        $stock = Stock::factory()->create([
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $location->id,
            'quantity' => $quantity,
        ]);

        return [$household, $member, $householdProduct, $location, $stock];
    }
}
