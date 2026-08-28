<?php

namespace Tests\Feature\Households;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_create_household(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->postJson('/api/households', [
                'name' => 'Some name idk',
            ]);

        $response->assertCreated();
        $response->assertJsonPath('data.name', 'Some name idk');
        $response->assertJsonPath('data.role', 'owner');
        $response->assertJsonStructure(['data' => ['uuid', 'name', 'role']]);
    }

    public function test_guest_cannot_create_household(): void
    {
        $response = $this->postJson('/api/households', [
            'name' => 'Some name idk',
        ]);

        $response->assertUnauthorized();
        $this->assertDatabaseCount('households', 0);
    }

    public function test_unverified_user_cannot_create_household(): void
    {
        $user = User::factory()->unverified()->create();

        $response = $this->actingAs($user)->postJson('/api/households', [
            'name' => 'Some name idk',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseCount('households', 0);
    }

    public function test_household_name_is_required(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/households', [
            'name' => '',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
        $this->assertDatabaseMissing('households', []);
    }

    public function test_household_name_must_have_at_leat_3_characters(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/households', [
            'name' => 'ad',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('name');
        $this->assertDatabaseMissing('households', []);
    }

    public function test_owner_can_add_existing_user_to_the_household(): void
    {
        $owner = User::factory()->create();
        $household = Household::factory()->create();
        $household->householdMemberships()->create([
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);
        $someone = User::factory()->create();

        $response = $this->actingAs($owner)->postJson(
            "/api/households/{$household->uuid}/members",
            ['email' => $someone->email]
        );

        $response->assertCreated();
        $response->assertJsonPath('data.user_id', $someone->getKey());
        $response->assertJsonPath('data.role', 'member');
        $response->assertJsonStructure([
            'data' => [
                'household_uuid',
                'user_id',
                'role',
            ],
        ]);
        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->getKey(),
            'user_id' => $someone->getKey(),
            'role' => HouseholdRole::Member->value,
        ]);
    }

    public function test_guest_cannot_add_household_member(): void
    {
        $household = Household::factory()->create();
        $someone = User::factory()->create();

        $response = $this->postJson(
            "/api/households/{$household->uuid}/members",
            ['email' => $someone->email],
        );

        $response->assertUnauthorized();
        $this->assertDatabaseCount('household_memberships', 0);
    }

    public function test_non_owner_cannot_add_household_member_via_api(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $someone = User::factory()->create();

        $household->householdMemberships()->create([
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);
        $household->householdMemberships()->create([
            'user_id' => $member->id,
            'role' => HouseholdRole::Member,
        ]);

        $response = $this->actingAs($member)->postJson(
            "/api/households/{$household->uuid}/members",
            ['email' => $someone->email],
        );

        $response->assertForbidden();
        $this->assertDatabaseMissing('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $someone->id,
        ]);
    }

    public function test_owner_cannot_add_same_member_twice_via_api(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $household->householdMemberships()->create([
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);
        $household->householdMemberships()->create([
            'user_id' => $member->id,
            'role' => HouseholdRole::Member,
        ]);

        $response = $this->actingAs($owner)->postJson(
            "/api/households/{$household->uuid}/members",
            ['email' => $member->email],
        );

        $response->assertConflict();
        $response->assertJsonPath('message', 'Пользователь уже является участником дома.');
        $this->assertSame(
            1,
            $household->householdMemberships()
                ->where('user_id', $member->id)
                ->count(),
        );
    }

    public function test_owner_cannot_add_unknown_user_via_api(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();

        $household->householdMemberships()->create([
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);

        $response = $this->actingAs($owner)->postJson(
            "/api/households/{$household->uuid}/members",
            ['email' => 'missing@example.org'],
        );

        $response->assertNotFound();
        $this->assertDatabaseCount('household_memberships', 1);
    }

    public function test_verified_user_can_list_only_their_households(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $ownedHousehold = Household::factory()->create(['name' => 'Owned Home']);
        $joinedHousehold = Household::factory()->create(['name' => 'Joined Home']);
        $foreignHousehold = Household::factory()->create(['name' => 'Foreign Home']);

        $ownedHousehold->householdMemberships()->create([
            'user_id' => $user->id,
            'role' => HouseholdRole::Owner,
        ]);
        $joinedHousehold->householdMemberships()->create([
            'user_id' => $user->id,
            'role' => HouseholdRole::Member,
        ]);
        $foreignHousehold->householdMemberships()->create([
            'user_id' => $anotherUser->id,
            'role' => HouseholdRole::Owner,
        ]);

        $response = $this->actingAs($user)->getJson('/api/households');

        $response->assertOk();
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'uuid' => $ownedHousehold->uuid,
            'name' => 'Owned Home',
            'role' => HouseholdRole::Owner->value,
        ]);
        $response->assertJsonFragment([
            'uuid' => $joinedHousehold->uuid,
            'name' => 'Joined Home',
            'role' => HouseholdRole::Member->value,
        ]);
        $response->assertJsonMissing([
            'uuid' => $foreignHousehold->uuid,
        ]);
    }

    public function test_guest_cannot_list_households(): void
    {
        $this->getJson('/api/households')->assertUnauthorized();
    }

    public function test_owner_can_transfer_household_ownership_via_api(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();

        $household->householdMemberships()->create([
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);
        $household->householdMemberships()->create([
            'user_id' => $member->id,
            'role' => HouseholdRole::Member,
        ]);

        $response = $this->actingAs($owner)->patchJson(
            "/api/households/{$household->uuid}/owner",
            ['new_owner_user_id' => $member->id],
        );

        $response->assertOk();
        $response->assertJsonPath('data.household_uuid', $household->uuid);
        $response->assertJsonPath('data.owner_user_id', $member->id);
        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Member->value,
        ]);
        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $member->id,
            'role' => HouseholdRole::Owner->value,
        ]);
    }

    public function test_guest_cannot_transfer_household_ownership(): void
    {
        $household = Household::factory()->create();

        $this->patchJson(
            "/api/households/{$household->uuid}/owner",
            ['new_owner_user_id' => 1],
        )->assertUnauthorized();
    }

    public function test_new_owner_user_id_is_required(): void
    {
        $owner = User::factory()->create();
        $household = Household::factory()->create();

        $household->householdMemberships()->create([
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);

        $response = $this->actingAs($owner)->patchJson(
            "/api/households/{$household->uuid}/owner",
            [],
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('new_owner_user_id');
    }

    public function test_non_owner_cannot_transfer_household_ownership_via_api(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $anotherMember = User::factory()->create();

        $household->householdMemberships()->create([
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);
        $household->householdMemberships()->create([
            'user_id' => $member->id,
            'role' => HouseholdRole::Member,
        ]);
        $household->householdMemberships()->create([
            'user_id' => $anotherMember->id,
            'role' => HouseholdRole::Member,
        ]);

        $response = $this->actingAs($member)->patchJson(
            "/api/households/{$household->uuid}/owner",
            ['new_owner_user_id' => $anotherMember->id],
        );

        $response->assertForbidden();
        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner->value,
        ]);
    }

    public function test_ownership_cannot_be_transferred_to_outsider_via_api(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        $household->householdMemberships()->create([
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);

        $response = $this->actingAs($owner)->patchJson(
            "/api/households/{$household->uuid}/owner",
            ['new_owner_user_id' => $outsider->id],
        );

        $response->assertNotFound();
        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner->value,
        ]);
    }

    public function test_owner_cannot_transfer_ownership_to_themselves_via_api(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();

        $household->householdMemberships()->create([
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);

        $response = $this->actingAs($owner)->patchJson(
            "/api/households/{$household->uuid}/owner",
            ['new_owner_user_id' => $owner->id],
        );

        $response->assertConflict();
        $response->assertJsonPath('message', 'Владелец не может передать права самому себе.');
        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner->value,
        ]);
    }
}
