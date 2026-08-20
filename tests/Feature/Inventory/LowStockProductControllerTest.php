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

class LowStockProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_list_only_low_stock_products_for_household(): void
    {
        [$household, $member] = $this->householdWithMember();
        $location = StorageLocation::factory()->create(['household_id' => $household->id]);
        $oldest = $this->lowStockProduct($household, 'Rice', '2026-08-18 10:00:00');
        $newest = $this->lowStockProduct($household, 'Milk', '2026-08-19 10:00:00');
        Stock::factory()->create([
            'household_product_id' => $oldest->id,
            'storage_location_id' => $location->id,
            'quantity' => '25.500',
        ]);
        HouseholdProduct::factory()->create(['household_id' => $household->id, 'low_stock_since' => null]);
        HouseholdProduct::factory()->create([
            'low_stock_since' => CarbonImmutable::parse('2026-08-17 10:00:00'),
        ]);

        $response = $this->actingAs($member)->getJson(
            "/api/households/{$household->uuid}/low-stock-products",
        );

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.uuid', $oldest->product->uuid)
            ->assertJsonPath('data.0.name', 'Rice')
            ->assertJsonPath('data.0.total_quantity', '25.500')
            ->assertJsonPath('data.0.is_low_stock', true)
            ->assertJsonPath('data.0.low_stock_since', '2026-08-18T10:00:00.000000Z')
            ->assertJsonPath('data.1.uuid', $newest->product->uuid);
    }

    public function test_guest_and_outsider_cannot_list_low_stock_products(): void
    {
        $household = Household::factory()->create();
        $url = "/api/households/{$household->uuid}/low-stock-products";

        $this->getJson($url)->assertUnauthorized();
        $this->actingAs(User::factory()->create())->getJson($url)->assertForbidden();
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

    private function lowStockProduct(Household $household, string $name, string $since): HouseholdProduct
    {
        $product = Product::factory()->mass()->create(['name' => $name]);

        return HouseholdProduct::factory()->create([
            'household_id' => $household->id,
            'product_id' => $product->id,
            'low_stock_threshold' => '100.000',
            'low_stock_since' => CarbonImmutable::parse($since, 'UTC'),
        ]);
    }
}
