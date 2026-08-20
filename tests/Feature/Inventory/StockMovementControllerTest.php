<?php

namespace Tests\Feature\Inventory;

use App\Enums\HouseholdRole;
use App\Enums\StockMovementType;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class StockMovementControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_list_only_their_household_movements_newest_first(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph();
        $older = $this->movement($household, $householdProduct, $location, $member, [
            'created_at' => Carbon::parse('2026-01-01 10:00:00'),
        ]);
        $newer = $this->movement($household, $householdProduct, $location, $member, [
            'type' => StockMovementType::Consumption,
            'input_quantity' => '5.000',
            'quantity_delta' => '-5.000',
            'quantity_before' => '10.000',
            'quantity_after' => '5.000',
            'created_at' => Carbon::parse('2026-01-02 10:00:00'),
        ]);
        StockMovement::factory()->create();

        $response = $this->actingAs($member)
            ->getJson("/api/households/{$household->uuid}/stock-movements");

        $response->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.uuid', $newer->uuid)
            ->assertJsonPath('data.1.uuid', $older->uuid)
            ->assertJsonPath('data.0.product.uuid', $householdProduct->product->uuid)
            ->assertJsonPath('data.0.storage_location.uuid', $location->uuid)
            ->assertJsonPath('data.0.actor.id', $member->getKey())
            ->assertJsonStructure([
                'data' => [[
                    'uuid',
                    'type',
                    'product' => ['uuid', 'name'],
                    'storage_location' => ['uuid', 'name'],
                    'actor' => ['id', 'name'],
                    'input' => ['quantity', 'unit'],
                    'quantity_delta',
                    'quantity_before',
                    'quantity_after',
                    'created_at',
                ]],
                'links',
                'meta',
            ]);
    }

    public function test_history_can_be_filtered_by_product_and_type(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph();
        $otherProduct = Product::factory()->mass()->create();
        $otherHouseholdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->getKey(),
            'product_id' => $otherProduct->getKey(),
        ]);

        $expected = $this->movement($household, $householdProduct, $location, $member, [
            'type' => StockMovementType::Consumption,
            'quantity_delta' => '-5.000',
            'quantity_before' => '10.000',
            'quantity_after' => '5.000',
        ]);
        $this->movement($household, $householdProduct, $location, $member);
        $this->movement($household, $otherHouseholdProduct, $location, $member, [
            'type' => StockMovementType::Consumption,
            'quantity_delta' => '-5.000',
            'quantity_before' => '10.000',
            'quantity_after' => '5.000',
        ]);

        $response = $this->actingAs($member)->getJson(
            "/api/households/{$household->uuid}/stock-movements"
            . "?product_uuid={$householdProduct->product->uuid}"
            . '&type=consumption',
        );

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.uuid', $expected->uuid);
    }

    public function test_history_is_paginated_with_requested_page_size(): void
    {
        [$household, $member, $householdProduct, $location] = $this->inventoryGraph();

        for ($index = 0; $index < 3; $index++) {
            $this->movement($household, $householdProduct, $location, $member);
        }

        $this->actingAs($member)
            ->getJson("/api/households/{$household->uuid}/stock-movements?per_page=2")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonPath('meta.total', 3);
    }

    public function test_movement_filters_are_validated(): void
    {
        [$household, $member] = $this->inventoryGraph();

        $this->actingAs($member)
            ->getJson("/api/households/{$household->uuid}/stock-movements?product_uuid=nope&type=sale&per_page=101")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_uuid', 'type', 'per_page']);
    }

    public function test_outsider_cannot_view_household_movements(): void
    {
        [$household] = $this->inventoryGraph();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/households/{$household->uuid}/stock-movements")
            ->assertForbidden();
    }

    public function test_guest_cannot_view_household_movements(): void
    {
        $household = Household::factory()->create();

        $this->getJson("/api/households/{$household->uuid}/stock-movements")
            ->assertUnauthorized();
    }

    private function movement(
        Household $household,
        HouseholdProduct $householdProduct,
        StorageLocation $location,
        User $actor,
        array $attributes = [],
    ): StockMovement {
        return StockMovement::factory()->create(array_merge([
            'household_id' => $household->getKey(),
            'household_product_id' => $householdProduct->getKey(),
            'product_id' => $householdProduct->product->getKey(),
            'storage_location_id' => $location->getKey(),
            'actor_user_id' => $actor->getKey(),
            'product_name' => $householdProduct->product->name,
            'storage_location_name' => $location->name,
            'actor_name' => $actor->name,
        ], $attributes));
    }

    private function inventoryGraph(): array
    {
        $household = Household::factory()->create();
        $member = User::factory()->create();
        HouseholdMembership::factory()->create([
            'household_id' => $household->getKey(),
            'user_id' => $member->getKey(),
            'role' => HouseholdRole::Member,
        ]);
        $product = Product::factory()->mass()->create();
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->getKey(),
            'product_id' => $product->getKey(),
        ]);
        $location = StorageLocation::factory()->create([
            'household_id' => $household->getKey(),
        ]);

        return [$household, $member, $householdProduct, $location];
    }
}
