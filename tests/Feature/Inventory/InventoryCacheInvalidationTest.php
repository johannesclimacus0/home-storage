<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\AddProductToHouseholdAction;
use App\Actions\Inventory\AddStockAction;
use App\Actions\Inventory\ConsumeStockAction;
use App\Actions\Inventory\ListHouseholdProductsAction;
use App\Actions\Inventory\RemoveProductFromHouseholdAction;
use App\Actions\Inventory\UpdateHouseholdProductAction;
use App\DTO\Inventory\AddProductToHouseholdData;
use App\DTO\Inventory\AddStockData;
use App\DTO\Inventory\ConsumeStockData;
use App\DTO\Inventory\UpdateHouseholdProductData;
use App\Enums\MeasurementUnit;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\Product;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

final class InventoryCacheInvalidationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_inventory_mutations_invalidate_household_inventory_cache_after_commit(): void
    {
        $household = Household::factory()->create();
        $member = User::factory()->create();
        HouseholdMembership::factory()->for($household)->for($member, 'user')->create();
        $product = Product::factory()->mass()->create(['name' => 'Milk']);
        $location = StorageLocation::factory()->for($household)->create();
        $list = $this->app->make(ListHouseholdProductsAction::class);

        $this->assertCount(0, $list->handle($household->uuid, $member->getKey()));

        $this->app->make(AddProductToHouseholdAction::class)->handle(
            new AddProductToHouseholdData(
                householdUuid: $household->uuid,
                actorUserId: $member->getKey(),
                productUuid: $product->uuid,
                lowStockThreshold: '100'
            )
        );

        $afterAdd = $list->handle($household->uuid, $member->getKey());
        $this->assertCount(1, $afterAdd);
        $this->assertSame($product->uuid, $afterAdd->first()['uuid']);

        $this->app->make(UpdateHouseholdProductAction::class)->handle(
            new UpdateHouseholdProductData(
                householdUuid: $household->uuid,
                actorUserId: $member->getKey(),
                productUuid: $product->uuid,
                lowStockThreshold: '200'
            )
        );

        $afterUpdate = $list->handle($household->uuid, $member->getKey());
        $this->assertSame('200.000', $afterUpdate->first()['low_stock_threshold']);

        $this->app->make(AddStockAction::class)->handle(new AddStockData(
            householdUuid: $household->uuid,
            actorUserId: $member->getKey(),
            productUuid: $product->uuid,
            storageLocationUuid: $location->uuid,
            quantity: '1',
            unit: MeasurementUnit::Kilogram
        ));

        $afterStock = $list->handle($household->uuid, $member->getKey());
        $this->assertSame('1000.000', $afterStock->first()['total_quantity']);

        $this->app->make(ConsumeStockAction::class)->handle(new ConsumeStockData(
            householdUuid: $household->uuid,
            actorUserId: $member->getKey(),
            productUuid: $product->uuid,
            storageLocationUuid: $location->uuid,
            quantity: '250',
            unit: MeasurementUnit::Gram
        ));

        $afterConsumption = $list->handle($household->uuid, $member->getKey());
        $this->assertSame('750.000', $afterConsumption->first()['total_quantity']);

        $this->app->make(RemoveProductFromHouseholdAction::class)->handle(
            $household->uuid,
            $member->getKey(),
            $product->uuid
        );

        $this->assertCount(0, $list->handle($household->uuid, $member->getKey()));
    }
}
