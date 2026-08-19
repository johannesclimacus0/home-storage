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

class ConsumeStockControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_consume_stock_via_api(): void
    {
        [$household, $member, $householdProduct, $location, $stock] = $this->inventoryGraph();

        $this->actingAs($member)
            ->postJson($this->url($household, $householdProduct), [
                'storage_location_uuid' => $location->uuid,
                'quantity' => '350',
                'unit' => 'g',
            ])
            ->assertOk()
            ->assertJsonPath('data.consumed_quantity', '350.000')
            ->assertJsonPath('data.unit', 'g')
            ->assertJsonPath('data.location_quantity', '650.000')
            ->assertJsonPath('data.total_quantity', '650.000');

        $this->assertSame('650.000', $stock->refresh()->quantity);
    }

    public function test_insufficient_stock_returns_conflict_and_rolls_back(): void
    {
        [$household, $member, $householdProduct, $location, $stock] = $this->inventoryGraph();

        $this->actingAs($member)
            ->postJson($this->url($household, $householdProduct), [
                'storage_location_uuid' => $location->uuid,
                'quantity' => '1001',
                'unit' => 'g',
            ])
            ->assertConflict()
            ->assertJsonPath('message', 'Insufficient stock.');

        $this->assertSame('1000.000', $stock->refresh()->quantity);
    }

    public function test_request_is_validated(): void
    {
        [$household, $member, $householdProduct] = $this->inventoryGraph();

        $this->actingAs($member)
            ->postJson($this->url($household, $householdProduct), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['storage_location_uuid', 'quantity', 'unit']);
    }

    public function test_guest_cannot_consume_stock(): void
    {
        [$household, , $householdProduct] = $this->inventoryGraph();

        $this->postJson($this->url($household, $householdProduct), [])
            ->assertUnauthorized();
    }

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
        ]);
        $location = StorageLocation::factory()->create(['household_id' => $household->id]);
        $stock = Stock::factory()->create([
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $location->id,
            'quantity' => '1000',
        ]);

        return [$household, $member, $householdProduct, $location, $stock];
    }

    private function url(Household $household, HouseholdProduct $householdProduct): string
    {
        return "/api/households/{$household->uuid}/products/{$householdProduct->product->uuid}/consume";
    }
}
