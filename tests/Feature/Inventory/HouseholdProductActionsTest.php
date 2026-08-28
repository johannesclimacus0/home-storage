<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\AddProductToHouseholdAction;
use App\DTO\Inventory\AddProductToHouseholdData;
use App\Enums\HouseholdRole;
use App\Enums\MeasurementType;
use App\Exceptions\Inventory\HouseholdProductConflict;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class HouseholdProductActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_add_catalog_product_to_household(): void
    {
        [$household, $member] = $this->householdWithMember();
        $product = Product::factory()->volume()->create(['name' => 'Milk']);

        $result = $this->action()->handle(new AddProductToHouseholdData(
            householdUuid: $household->uuid,
            actorUserId: $member->id,
            productUuid: $product->uuid,
            lowStockThreshold: '1500.5',
        ));

        $this->assertSame($household->uuid, $result->householdUuid);
        $this->assertSame($product->uuid, $result->productUuid);
        $this->assertSame('Milk', $result->productName);
        $this->assertSame(MeasurementType::Volume, $result->measurementType);
        $this->assertSame('1500.500', $result->lowStockThreshold);
        $this->assertDatabaseHas('household_products', [
            'household_id' => $household->id,
            'product_id' => $product->id,
            'low_stock_threshold' => '1500.500',
        ]);
    }

    public function test_outsider_cannot_add_product_to_household(): void
    {
        $household = Household::factory()->create();
        $outsider = User::factory()->create();
        $product = Product::factory()->create();

        $this->assertThrows(
            fn () => $this->action()->handle(new AddProductToHouseholdData(
                householdUuid: $household->uuid,
                actorUserId: $outsider->id,
                productUuid: $product->uuid,
                lowStockThreshold: '0',
            )),
            ModelNotFoundException::class,
        );

        $this->assertDatabaseCount('household_products', 0);
    }

    public function test_unknown_catalog_product_cannot_be_added(): void
    {
        [$household, $member] = $this->householdWithMember();

        $this->assertThrows(
            fn () => $this->action()->handle(new AddProductToHouseholdData(
                householdUuid: $household->uuid,
                actorUserId: $member->id,
                productUuid: (string) Str::uuid(),
                lowStockThreshold: '0',
            )),
            ModelNotFoundException::class,
        );
    }

    public function test_product_cannot_be_added_to_same_household_twice(): void
    {
        [$household, $member] = $this->householdWithMember();
        $product = Product::factory()->create();
        HouseholdProduct::factory()->create([
            'household_id' => $household->id,
            'product_id' => $product->id,
        ]);

        $this->assertThrows(
            fn () => $this->action()->handle(new AddProductToHouseholdData(
                householdUuid: $household->uuid,
                actorUserId: $member->id,
                productUuid: $product->uuid,
                lowStockThreshold: '10',
            )),
            HouseholdProductConflict::class,
            'Продукт уже добавлен в этот дом.',
        );

        $this->assertDatabaseCount('household_products', 1);
    }

    public function test_low_stock_threshold_cannot_be_negative(): void
    {
        [$household, $member] = $this->householdWithMember();
        $product = Product::factory()->mass()->create();

        $this->assertThrows(
            fn () => $this->action()->handle(new AddProductToHouseholdData(
                householdUuid: $household->uuid,
                actorUserId: $member->id,
                productUuid: $product->uuid,
                lowStockThreshold: '-0.001',
            )),
            InvalidArgumentException::class,
            'Порог не может быть отрицательным.',
        );
    }

    public function test_countable_product_requires_whole_number_threshold(): void
    {
        [$household, $member] = $this->householdWithMember();
        $product = Product::factory()->counted()->create();

        $this->assertThrows(
            fn () => $this->action()->handle(new AddProductToHouseholdData(
                householdUuid: $household->uuid,
                actorUserId: $member->id,
                productUuid: $product->uuid,
                lowStockThreshold: '1.5',
            )),
            InvalidArgumentException::class,
            'Для штучных продуктов порог должен быть целым числом.',
        );
    }

    public function test_threshold_cannot_have_more_than_three_decimal_places(): void
    {
        [$household, $member] = $this->householdWithMember();
        $product = Product::factory()->mass()->create();

        $this->assertThrows(
            fn () => $this->action()->handle(new AddProductToHouseholdData(
                householdUuid: $household->uuid,
                actorUserId: $member->id,
                productUuid: $product->uuid,
                lowStockThreshold: '1.2345',
            )),
            InvalidArgumentException::class,
            'Порог должен быть числом не более чем с тремя знаками после запятой.',
        );
    }

    private function action(): AddProductToHouseholdAction
    {
        return $this->app->make(AddProductToHouseholdAction::class);
    }

    private function householdWithMember(): array
    {
        $household = Household::factory()->create();
        $member = User::factory()->create();

        HouseholdMembership::factory()->create([
            'household_id' => $household->id,
            'user_id' => $member->id,
            'role' => HouseholdRole::Member,
        ]);

        return [$household, $member];
    }
}
