<?php

namespace Tests\Feature\Inventory;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_add_catalog_product_to_household(): void
    {
        [$household, $member] = $this->householdWithMember();
        $product = Product::factory()->volume()->create(['name' => 'Milk']);

        $response = $this->actingAs($member)->postJson(
            "/api/households/{$household->uuid}/products",
            [
                'product_uuid' => $product->uuid,
                'low_stock_threshold' => '1.5',
            ],
        );

        $response->assertCreated()
            ->assertJsonPath('data.uuid', $product->uuid)
            ->assertJsonPath('data.name', 'Milk')
            ->assertJsonPath('data.measurement_type', 'volume')
            ->assertJsonPath('data.low_stock_threshold', '1.500')
            ->assertJsonPath('data.total_quantity', '0.000');

        $this->assertDatabaseHas('household_products', [
            'household_id' => $household->id,
            'product_id' => $product->id,
            'low_stock_threshold' => '1.500',
        ]);
    }

    public function test_member_can_list_household_products_with_total_quantity(): void
    {
        [$household, $member] = $this->householdWithMember();
        $product = Product::factory()->mass()->create(['name' => 'Flour']);
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
            'product_id' => $product->id,
            'low_stock_threshold' => '350',
        ]);
        $location = StorageLocation::factory()->create(['household_id' => $household->id]);
        Stock::factory()->create([
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $location->id,
            'quantity' => '125.250',
        ]);
        Product::factory()->create(['name' => 'Foreign catalog product']);

        $response = $this->actingAs($member)->getJson(
            "/api/households/{$household->uuid}/products",
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $product->uuid)
            ->assertJsonPath('data.0.total_quantity', '125.250');
    }

    public function test_member_can_show_household_product(): void
    {
        [$household, $member] = $this->householdWithMember();
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
            'low_stock_threshold' => '2',
        ]);

        $this->actingAs($member)
            ->getJson("/api/households/{$household->uuid}/products/{$householdProduct->product->uuid}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $householdProduct->product->uuid)
            ->assertJsonPath('data.low_stock_threshold', '2.000');
    }

    public function test_member_can_update_low_stock_threshold(): void
    {
        [$household, $member] = $this->householdWithMember();
        $product = Product::factory()->counted()->create();
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
            'product_id' => $product->id,
        ]);

        $this->actingAs($member)
            ->patchJson(
                "/api/households/{$household->uuid}/products/{$product->uuid}",
                ['low_stock_threshold' => '2'],
            )
            ->assertOk()
            ->assertJsonPath('data.low_stock_threshold', '2.000');

        $this->assertDatabaseHas('household_products', [
            'id' => $householdProduct->id,
            'low_stock_threshold' => '2.000',
        ]);
    }

    public function test_fractional_threshold_for_countable_product_is_rejected(): void
    {
        [$household, $member] = $this->householdWithMember();
        $product = Product::factory()->counted()->create();
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
            'product_id' => $product->id,
            'low_stock_threshold' => '1',
        ]);

        $this->actingAs($member)
            ->patchJson(
                "/api/households/{$household->uuid}/products/{$product->uuid}",
                ['low_stock_threshold' => '1.5'],
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors('low_stock_threshold');

        $this->assertSame('1.000', $householdProduct->refresh()->low_stock_threshold);
    }

    public function test_member_can_remove_product_and_its_stocks_from_household(): void
    {
        [$household, $member] = $this->householdWithMember();
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
        ]);
        $location = StorageLocation::factory()->create(['household_id' => $household->id]);
        $stock = Stock::factory()->create([
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $location->id,
        ]);

        $this->actingAs($member)
            ->deleteJson(
                "/api/households/{$household->uuid}/products/{$householdProduct->product->uuid}",
            )
            ->assertNoContent();

        $this->assertDatabaseMissing('household_products', ['id' => $householdProduct->id]);
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
        $this->assertDatabaseHas('products', ['id' => $householdProduct->product_id]);
    }

    public function test_outsider_cannot_access_household_products(): void
    {
        $household = Household::factory()->create();
        $outsider = User::factory()->create();
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
        ]);

        $this->actingAs($outsider)
            ->getJson("/api/households/{$household->uuid}/products")
            ->assertForbidden();

        $this->actingAs($outsider)
            ->deleteJson(
                "/api/households/{$household->uuid}/products/{$householdProduct->product->uuid}",
            )
            ->assertForbidden();

        $this->assertDatabaseHas('household_products', ['id' => $householdProduct->id]);
    }

    public function test_store_validates_request_and_duplicate_product_is_a_conflict(): void
    {
        [$household, $member] = $this->householdWithMember();
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
        ]);
        $url = "/api/households/{$household->uuid}/products";

        $this->actingAs($member)
            ->postJson($url)
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_uuid', 'low_stock_threshold']);

        $this->actingAs($member)
            ->postJson($url, [
                'product_uuid' => $householdProduct->product->uuid,
                'low_stock_threshold' => '0',
            ])
            ->assertConflict();
    }

    public function test_guest_cannot_list_household_products(): void
    {
        $household = Household::factory()->create();

        $this->getJson("/api/households/{$household->uuid}/products")
            ->assertUnauthorized();
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
