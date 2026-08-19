<?php

namespace Tests\Feature\Inventory;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_add_stock_via_api(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph();

        $this->actingAs($member)
            ->postJson($this->url($household, $householdProduct), [
                'storage_location_uuid' => $location->uuid,
                'quantity' => '0.5',
                'unit' => 'kg',
            ])
            ->assertOk()
            ->assertJsonPath('data.product_uuid', $householdProduct->product->uuid)
            ->assertJsonPath('data.storage_location_uuid', $location->uuid)
            ->assertJsonPath('data.added_quantity', '500.000')
            ->assertJsonPath('data.unit', 'g')
            ->assertJsonPath('data.location_quantity', '500.000')
            ->assertJsonPath('data.total_quantity', '500.000');
    }

    public function test_request_fields_are_validated(): void
    {
        [$household, $member, $householdProduct] = $this->inventoryGraph();

        $this->actingAs($member)
            ->postJson($this->url($household, $householdProduct), [
                'storage_location_uuid' => 'invalid',
                'quantity' => 5,
                'unit' => 'box',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'storage_location_uuid',
                'quantity',
                'unit',
            ]);
    }

    public function test_incompatible_unit_returns_validation_error(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph();

        $this->actingAs($member)
            ->postJson($this->url($household, $householdProduct), [
                'storage_location_uuid' => $location->uuid,
                'quantity' => '1',
                'unit' => 'l',
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('unit');
    }

    public function test_unknown_or_foreign_inventory_entities_return_not_found(): void
    {
        [$household, $member, $householdProduct] = $this->inventoryGraph();
        $foreignLocation = StorageLocation::factory()->create();

        $this->actingAs($member)
            ->postJson($this->url($household, $householdProduct), [
                'storage_location_uuid' => $foreignLocation->uuid,
                'quantity' => '1',
                'unit' => 'g',
            ])
            ->assertNotFound();

        $catalogOnlyProduct = Product::factory()->mass()->create();

        $this->actingAs($member)
            ->postJson(
                "/api/households/{$household->uuid}/products/{$catalogOnlyProduct->uuid}/stocks",
                [
                    'storage_location_uuid' => $foreignLocation->uuid,
                    'quantity' => '1',
                    'unit' => 'g',
                ],
            )
            ->assertNotFound();
    }

    public function test_guest_cannot_add_stock(): void
    {
        [$household, , $householdProduct, $location] = $this->inventoryGraph();

        $this->postJson($this->url($household, $householdProduct), [
            'storage_location_uuid' => $location->uuid,
            'quantity' => '1',
            'unit' => 'g',
        ])->assertUnauthorized();
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
        ]);
        $location = StorageLocation::factory()->create(['household_id' => $household->id]);

        return [$household, $member, $householdProduct, $location];
    }

    private function url(Household $household, HouseholdProduct $householdProduct): string
    {
        return "/api/households/{$household->uuid}/products/{$householdProduct->product->uuid}/stocks";
    }
}
