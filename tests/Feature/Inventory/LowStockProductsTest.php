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
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockProductsTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_consuming_and_replenishing_stock_updates_low_stock_state(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph();
        Stock::factory()->create([
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $location->id,
            'quantity' => '150.000',
        ]);
        $firstDetectedAt = CarbonImmutable::parse('2026-08-20 10:00:00');
        CarbonImmutable::setTestNow($firstDetectedAt);

        $this->actingAs($member)->postJson(
            "/api/households/{$household->uuid}/products/{$householdProduct->product->uuid}/consume",
            ['storage_location_uuid' => $location->uuid, 'quantity' => '60', 'unit' => 'g'],
        )->assertOk()->assertJsonPath('data.total_quantity', '90.000');

        $this->assertTrue($householdProduct->refresh()->low_stock_since->equalTo($firstDetectedAt));

        CarbonImmutable::setTestNow($firstDetectedAt->addDay());
        $this->actingAs($member)->postJson(
            "/api/households/{$household->uuid}/products/{$householdProduct->product->uuid}/consume",
            ['storage_location_uuid' => $location->uuid, 'quantity' => '10', 'unit' => 'g'],
        )->assertOk();

        $this->assertTrue($householdProduct->refresh()->low_stock_since->equalTo($firstDetectedAt));

        $this->actingAs($member)->postJson(
            "/api/households/{$household->uuid}/products/{$householdProduct->product->uuid}/stocks",
            ['storage_location_uuid' => $location->uuid, 'quantity' => '100', 'unit' => 'g'],
        )->assertOk()->assertJsonPath('data.total_quantity', '180.000');

        $this->assertNull($householdProduct->refresh()->low_stock_since);
    }

    public function test_adding_household_product_and_updating_threshold_recalculate_state(): void
    {
        $household = Household::factory()->create();
        $member = User::factory()->create();
        HouseholdMembership::factory()->create([
            'household_id' => $household->id,
            'user_id' => $member->id,
            'role' => HouseholdRole::Member,
        ]);
        $product = Product::factory()->mass()->create();
        CarbonImmutable::setTestNow('2026-08-20 10:00:00');

        $this->actingAs($member)->postJson(
            "/api/households/{$household->uuid}/products",
            ['product_uuid' => $product->uuid, 'low_stock_threshold' => '100'],
        )->assertCreated();

        $householdProduct = HouseholdProduct::query()->where('product_id', $product->id)->firstOrFail();
        $this->assertNotNull($householdProduct->low_stock_since);

        $location = StorageLocation::factory()->create(['household_id' => $household->id]);
        Stock::factory()->create([
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $location->id,
            'quantity' => '50.000',
        ]);

        $this->actingAs($member)->patchJson(
            "/api/households/{$household->uuid}/products/{$product->uuid}",
            ['low_stock_threshold' => '25'],
        )->assertOk()->assertJsonPath('data.is_low_stock', false);

        $this->assertNull($householdProduct->refresh()->low_stock_since);
    }

    /** @return array{Household, User, HouseholdProduct, StorageLocation} */
    private function inventoryGraph(): array
    {
        $household = Household::factory()->create();
        $member = User::factory()->create();
        HouseholdMembership::factory()->create([
            'household_id' => $household->id,
            'user_id' => $member->id,
            'role' => HouseholdRole::Member,
        ]);
        $product = Product::factory()->mass()->create();
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
            'product_id' => $product->id,
            'low_stock_threshold' => '100.000',
            'low_stock_since' => null,
        ]);
        $location = StorageLocation::factory()->create(['household_id' => $household->id]);

        return [$household, $member, $householdProduct, $location];
    }
}
