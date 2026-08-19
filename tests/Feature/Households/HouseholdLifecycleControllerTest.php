<?php

namespace Tests\Feature\Households;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\Stock;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdLifecycleControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_view_household_and_members(): void
    {
        [$household, $owner, $members] = $this->householdWithMembers(1);
        $member = $members[0];

        $this->actingAs($member)
            ->getJson("/api/households/{$household->uuid}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $household->uuid)
            ->assertJsonPath('data.role', 'member')
            ->assertJsonCount(2, 'data.members');

        $this->actingAs($owner)
            ->getJson("/api/households/{$household->uuid}/members")
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.user_id', $owner->id)
            ->assertJsonPath('data.0.role', 'owner');
    }

    public function test_outsider_cannot_view_household(): void
    {
        [$household] = $this->householdWithMembers();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/households/{$household->uuid}")
            ->assertForbidden();
    }

    public function test_owner_can_rename_household_but_member_cannot(): void
    {
        [$household, $owner, $members] = $this->householdWithMembers(1);
        $member = $members[0];

        $this->actingAs($member)
            ->patchJson("/api/households/{$household->uuid}", ['name' => 'Forbidden name'])
            ->assertForbidden();

        $this->actingAs($owner)
            ->patchJson("/api/households/{$household->uuid}", ['name' => '  Family home  '])
            ->assertOk()
            ->assertJsonPath('data.name', 'Family home');

        $this->assertSame('Family home', $household->refresh()->name);
    }

    public function test_owner_can_remove_member(): void
    {
        [$household, $owner, $members] = $this->householdWithMembers(1);
        $member = $members[0];

        $this->actingAs($owner)
            ->deleteJson("/api/households/{$household->uuid}/members/{$member->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_owner_cannot_remove_themselves_through_members_endpoint(): void
    {
        [$household, $owner] = $this->householdWithMembers();

        $this->actingAs($owner)
            ->deleteJson("/api/households/{$household->uuid}/members/{$owner->id}")
            ->assertConflict();

        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner->value,
        ]);
    }

    public function test_member_cannot_remove_another_member(): void
    {
        [$household, , $members] = $this->householdWithMembers(2);

        $this->actingAs($members[0])
            ->deleteJson("/api/households/{$household->uuid}/members/{$members[1]->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $members[1]->id,
        ]);
    }

    public function test_removing_unknown_member_returns_not_found(): void
    {
        [$household, $owner] = $this->householdWithMembers();
        $outsider = User::factory()->create();

        $this->actingAs($owner)
            ->deleteJson("/api/households/{$household->uuid}/members/{$outsider->id}")
            ->assertNotFound();
    }

    public function test_member_can_leave_household(): void
    {
        [$household, , $members] = $this->householdWithMembers(1);
        $member = $members[0];

        $this->actingAs($member)
            ->deleteJson("/api/households/{$household->uuid}/membership")
            ->assertOk()
            ->assertJsonPath('data.household_deleted', false)
            ->assertJsonPath('data.new_owner_user_id', null);

        $this->assertDatabaseMissing('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $member->id,
        ]);
    }

    public function test_owner_leaving_transfers_ownership_to_earliest_member(): void
    {
        [$household, $owner, $members] = $this->householdWithMembers(2);
        [$earliestMember, $laterMember] = $members;

        $this->actingAs($owner)
            ->deleteJson("/api/households/{$household->uuid}/membership")
            ->assertOk()
            ->assertJsonPath('data.household_deleted', false)
            ->assertJsonPath('data.new_owner_user_id', $earliestMember->id);

        $this->assertDatabaseMissing('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $owner->id,
        ]);
        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $earliestMember->id,
            'role' => HouseholdRole::Owner->value,
        ]);
        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $laterMember->id,
            'role' => HouseholdRole::Member->value,
        ]);
    }

    public function test_last_owner_leaving_deletes_household_and_its_data(): void
    {
        [$household, $owner] = $this->householdWithMembers();
        [$householdProduct, $location, $stock] = $this->inventoryGraph($household);

        $this->actingAs($owner)
            ->deleteJson("/api/households/{$household->uuid}/membership")
            ->assertOk()
            ->assertJsonPath('data.household_deleted', true);

        $this->assertDatabaseMissing('households', ['id' => $household->id]);
        $this->assertDatabaseMissing('household_products', ['id' => $householdProduct->id]);
        $this->assertDatabaseMissing('storage_locations', ['id' => $location->id]);
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_owner_can_delete_household_with_all_data(): void
    {
        [$household, $owner] = $this->householdWithMembers(1);
        [$householdProduct, $location, $stock] = $this->inventoryGraph($household);

        $this->actingAs($owner)
            ->deleteJson("/api/households/{$household->uuid}")
            ->assertNoContent();

        $this->assertDatabaseMissing('households', ['id' => $household->id]);
        $this->assertDatabaseMissing('household_products', ['id' => $householdProduct->id]);
        $this->assertDatabaseMissing('storage_locations', ['id' => $location->id]);
        $this->assertDatabaseMissing('stocks', ['id' => $stock->id]);
    }

    public function test_member_cannot_delete_household(): void
    {
        [$household, , $members] = $this->householdWithMembers(1);

        $this->actingAs($members[0])
            ->deleteJson("/api/households/{$household->uuid}")
            ->assertForbidden();

        $this->assertDatabaseHas('households', ['id' => $household->id]);
    }

    private function householdWithMembers(int $memberCount = 0): array
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();
        HouseholdMembership::factory()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);
        $members = [];

        for ($index = 0; $index < $memberCount; $index++) {
            $member = User::factory()->create();
            HouseholdMembership::factory()->create([
                'household_id' => $household->id,
                'user_id' => $member->id,
                'role' => HouseholdRole::Member,
            ]);
            $members[] = $member;
        }

        return [$household, $owner, $members];
    }

    private function inventoryGraph(Household $household): array
    {
        $householdProduct = HouseholdProduct::factory()->create([
            'household_id' => $household->id,
        ]);
        $location = StorageLocation::factory()->create([
            'household_id' => $household->id,
        ]);
        $stock = Stock::factory()->create([
            'household_product_id' => $householdProduct->id,
            'storage_location_id' => $location->id,
        ]);

        return [$householdProduct, $location, $stock];
    }
}
