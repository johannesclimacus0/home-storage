<?php

namespace Tests\Feature\Inventory;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMembership;
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
        )->assertNotFound();

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
