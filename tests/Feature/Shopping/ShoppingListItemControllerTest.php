<?php

namespace Tests\Feature\Shopping;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\ShoppingListItem;
use App\Models\Stock;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class ShoppingListItemControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_manage_household_shopping_list(): void
    {
        [$household, $member] = $this->householdWithMember();
        $product = Product::factory()->volume()->create(['name' => 'Test milk']);
        $baseUrl = "/api/households/{$household->uuid}/shopping-list-items";

        $createResponse = $this->actingAs($member)->postJson($baseUrl, [
            'product_uuid' => $product->uuid,
            'quantity' => '1.5',
            'unit' => 'l',
        ]);

        $createResponse->assertCreated()
            ->assertJsonPath('data.product.name', 'Test milk')
            ->assertJsonPath('data.quantity', '1500.000')
            ->assertJsonPath('data.unit', 'ml')
            ->assertJsonPath('data.completed_at', null)
            ->assertJsonPath('data.added_by.id', $member->id);

        $itemUuid = $createResponse->json('data.uuid');

        $this->actingAs($member)
            ->patchJson("{$baseUrl}/{$itemUuid}", ['quantity' => '2', 'unit' => 'l'])
            ->assertOk()
            ->assertJsonPath('data.quantity', '2000.000');

        $this->actingAs($member)
            ->patchJson("{$baseUrl}/{$itemUuid}/complete")
            ->assertOk()
            ->assertJsonPath('data.uuid', $itemUuid)
            ->assertJsonPath('data.product.name', 'Test milk');

        $this->assertNotNull(ShoppingListItem::where('uuid', $itemUuid)->firstOrFail()->completed_at);

        $this->actingAs($member)
            ->patchJson("{$baseUrl}/{$itemUuid}/reopen")
            ->assertOk()
            ->assertJsonPath('data.completed_at', null);

        $this->actingAs($member)
            ->deleteJson("{$baseUrl}/{$itemUuid}")
            ->assertNoContent();

        $this->assertDatabaseMissing('shopping_list_items', ['uuid' => $itemUuid]);
    }

    public function test_readding_product_updates_and_reopens_existing_item(): void
    {
        [$household, $member] = $this->householdWithMember();
        $product = Product::factory()->mass()->create();
        $item = ShoppingListItem::factory()->completed()->create([
            'household_id' => $household->id,
            'product_id' => $product->id,
            'added_by_user_id' => $member->id,
            'quantity' => '500',
        ]);

        $this->actingAs($member)->postJson(
            "/api/households/{$household->uuid}/shopping-list-items",
            ['product_uuid' => $product->uuid, 'quantity' => '1', 'unit' => 'kg'],
        )->assertCreated()
            ->assertJsonPath('data.uuid', $item->uuid)
            ->assertJsonPath('data.quantity', '1000.000')
            ->assertJsonPath('data.completed_at', null);

        $this->assertDatabaseCount('shopping_list_items', 1);
    }

    public function test_member_lists_only_household_items_and_outsider_is_forbidden(): void
    {
        [$household, $member] = $this->householdWithMember();
        $active = ShoppingListItem::factory()->create(['household_id' => $household->id]);
        $completed = ShoppingListItem::factory()->completed()->create(['household_id' => $household->id]);
        $foreign = ShoppingListItem::factory()->create();

        $this->actingAs($member)
            ->getJson("/api/households/{$household->uuid}/shopping-list-items")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.uuid', $active->uuid)
            ->assertJsonPath('data.1.uuid', $completed->uuid)
            ->assertJsonMissing(['uuid' => $foreign->uuid]);

        $this->actingAs(User::factory()->create())
            ->getJson("/api/households/{$household->uuid}/shopping-list-items")
            ->assertForbidden();
    }

    public function test_store_validates_quantity_unit_and_product_uuid(): void
    {
        [$household, $member] = $this->householdWithMember();

        $this->actingAs($member)
            ->postJson("/api/households/{$household->uuid}/shopping-list-items")
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['product_uuid', 'quantity', 'unit']);
    }

    public function test_member_can_purchase_item_into_stock_and_item_is_completed_once(): void
    {
        [$household, $member] = $this->householdWithMember();
        $product = Product::factory()->volume()->create(['name' => 'Test juice']);
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
            'product_id' => $product->id,
        ]);
        $location = StorageLocation::factory()->create(['household_id' => $household->id]);
        $item = ShoppingListItem::factory()->create([
            'household_id' => $household->id,
            'product_id' => $product->id,
            'added_by_user_id' => $member->id,
            'quantity' => '1500',
        ]);
        $url = "/api/households/{$household->uuid}/shopping-list-items/{$item->uuid}/purchase";

        $this->actingAs($member)
            ->postJson($url, ['storage_location_uuid' => $location->uuid])
            ->assertOk()
            ->assertJsonPath('data.uuid', $item->uuid)
            ->assertJsonPath('data.product.name', 'Test juice');

        $this->assertNotNull($item->refresh()->completed_at);
        $this->assertSame(
            '1500.000',
            Stock::query()
                ->where('household_product_id', $householdProduct->id)
                ->where('storage_location_id', $location->id)
                ->firstOrFail()
                ->quantity,
        );
        $this->assertDatabaseHas('stock_movements', [
            'household_id' => $household->id,
            'household_product_id' => $householdProduct->id,
            'input_quantity' => '1500.000',
            'input_unit' => 'ml',
        ]);

        $this->actingAs($member)
            ->postJson($url, ['storage_location_uuid' => $location->uuid])
            ->assertConflict();

        $this->assertSame('1500.000', $householdProduct->stocks()->firstOrFail()->quantity);
        $this->assertDatabaseCount('stock_movements', 1);
    }

    public function test_failed_stock_purchase_does_not_complete_item(): void
    {
        [$household, $member] = $this->householdWithMember();
        $item = ShoppingListItem::factory()->create([
            'household_id' => $household->id,
            'added_by_user_id' => $member->id,
        ]);
        $location = StorageLocation::factory()->create(['household_id' => $household->id]);

        $this->actingAs($member)
            ->postJson(
                "/api/households/{$household->uuid}/shopping-list-items/{$item->uuid}/purchase",
                ['storage_location_uuid' => $location->uuid],
            )
            ->assertNotFound();

        $this->assertNull($item->refresh()->completed_at);
        $this->assertDatabaseCount('stocks', 0);
        $this->assertDatabaseCount('stock_movements', 0);
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
