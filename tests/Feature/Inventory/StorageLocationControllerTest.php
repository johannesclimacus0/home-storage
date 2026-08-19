<?php

namespace Tests\Feature\Inventory;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\Stock;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StorageLocationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_household_member_can_create_storage_location_via_api(): void
    {
        [$household, $member] = $this->householdWithMember();

        $response = $this->actingAs($member)->postJson(
            "/api/households/{$household->uuid}/storage-locations",
            ['name' => 'Fridge'],
        );

        $response->assertCreated();
        $response->assertJsonPath('data.household_uuid', $household->uuid);
        $response->assertJsonPath('data.name', 'Fridge');
        $response->assertJsonStructure([
            'data' => ['uuid', 'household_uuid', 'name'],
        ]);
        $this->assertDatabaseHas('storage_locations', [
            'uuid' => $response->json('data.uuid'),
            'household_id' => $household->id,
            'name' => 'Fridge',
        ]);
    }

    public function test_guest_cannot_create_storage_location(): void
    {
        $household = Household::factory()->create();

        $this->postJson(
            "/api/households/{$household->uuid}/storage-locations",
            ['name' => 'Fridge'],
        )->assertUnauthorized();

        $this->assertDatabaseCount('storage_locations', 0);
    }

    public function test_storage_location_name_is_validated(): void
    {
        [$household, $member] = $this->householdWithMember();

        $response = $this->actingAs($member)->postJson(
            "/api/households/{$household->uuid}/storage-locations",
            ['name' => 'ad'],
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
        $this->assertDatabaseCount('storage_locations', 0);
    }

    public function test_outsider_cannot_create_storage_location_via_api(): void
    {
        $household = Household::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->postJson(
            "/api/households/{$household->uuid}/storage-locations",
            ['name' => 'Fridge'],
        )->assertForbidden();

        $this->assertDatabaseCount('storage_locations', 0);
    }

    public function test_duplicate_storage_location_returns_conflict(): void
    {
        [$household, $member] = $this->householdWithMember();
        StorageLocation::factory()->create([
            'household_id' => $household->id,
            'name' => 'Fridge',
        ]);

        $response = $this->actingAs($member)->postJson(
            "/api/households/{$household->uuid}/storage-locations",
            ['name' => 'Fridge'],
        );

        $response->assertConflict();
        $response->assertJsonPath(
            'message',
            'A storage location with this name already exists.',
        );
        $this->assertDatabaseCount('storage_locations', 1);
    }

    public function test_member_can_list_only_household_storage_locations(): void
    {
        [$household, $member] = $this->householdWithMember();
        $pantry = StorageLocation::factory()->create([
            'household_id' => $household->id,
            'name' => 'Pantry',
        ]);
        $fridge = StorageLocation::factory()->create([
            'household_id' => $household->id,
            'name' => 'Fridge',
        ]);
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
        ]);
        Stock::factory()->create([
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $fridge->id,
            'quantity' => '12.500',
        ]);
        StorageLocation::factory()->create(['name' => 'Foreign']);

        $this->actingAs($member)
            ->getJson("/api/households/{$household->uuid}/storage-locations")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.uuid', $fridge->uuid)
            ->assertJsonPath('data.1.uuid', $pantry->uuid);
    }

    public function test_member_can_show_storage_location(): void
    {
        [$household, $member] = $this->householdWithMember();
        $location = StorageLocation::factory()->create([
            'household_id' => $household->id,
            'name' => 'Freezer',
        ]);

        $this->actingAs($member)
            ->getJson("/api/households/{$household->uuid}/storage-locations/{$location->uuid}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $location->uuid)
            ->assertJsonPath('data.name', 'Freezer');
    }

    public function test_member_can_rename_storage_location(): void
    {
        [$household, $member] = $this->householdWithMember();
        $location = StorageLocation::factory()->create([
            'household_id' => $household->id,
            'name' => 'Old name',
        ]);

        $this->actingAs($member)
            ->patchJson(
                "/api/households/{$household->uuid}/storage-locations/{$location->uuid}",
                ['name' => '  New name  '],
            )
            ->assertOk()
            ->assertJsonPath('data.name', 'New name');

        $this->assertDatabaseHas('storage_locations', [
            'id' => $location->id,
            'name' => 'New name',
        ]);
    }

    public function test_location_cannot_be_renamed_to_duplicate_name(): void
    {
        [$household, $member] = $this->householdWithMember();
        StorageLocation::factory()->create([
            'household_id' => $household->id,
            'name' => 'Fridge',
        ]);
        $location = StorageLocation::factory()->create([
            'household_id' => $household->id,
            'name' => 'Pantry',
        ]);

        $this->actingAs($member)
            ->patchJson(
                "/api/households/{$household->uuid}/storage-locations/{$location->uuid}",
                ['name' => 'Fridge'],
            )
            ->assertConflict();

        $this->assertSame('Pantry', $location->refresh()->name);
    }

    public function test_member_can_delete_empty_storage_location(): void
    {
        [$household, $member] = $this->householdWithMember();
        $location = StorageLocation::factory()->create(['household_id' => $household->id]);
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
        ]);
        $zeroStock = Stock::factory()->create([
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $location->id,
            'quantity' => '0',
        ]);

        $this->actingAs($member)
            ->deleteJson("/api/households/{$household->uuid}/storage-locations/{$location->uuid}")
            ->assertNoContent();

        $this->assertDatabaseMissing('storage_locations', ['id' => $location->id]);
        $this->assertDatabaseMissing('stocks', ['id' => $zeroStock->id]);
    }

    public function test_storage_location_with_stock_cannot_be_deleted(): void
    {
        [$household, $member] = $this->householdWithMember();
        $location = StorageLocation::factory()->create(['household_id' => $household->id]);
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
        ]);
        Stock::factory()->create([
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $location->id,
            'quantity' => '0.001',
        ]);

        $this->actingAs($member)
            ->deleteJson("/api/households/{$household->uuid}/storage-locations/{$location->uuid}")
            ->assertConflict()
            ->assertJsonPath(
                'message',
                'Storage location contains stock and cannot be deleted.',
            );

        $this->assertDatabaseHas('storage_locations', ['id' => $location->id]);
    }

    public function test_location_from_another_household_is_not_accessible(): void
    {
        [$household, $member] = $this->householdWithMember();
        $foreignLocation = StorageLocation::factory()->create();

        $this->actingAs($member)
            ->getJson(
                "/api/households/{$household->uuid}/storage-locations/{$foreignLocation->uuid}",
            )
            ->assertNotFound();
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
