<?php

namespace Tests\Feature\Households;

use App\Actions\Households\AddHouseholdMemberAction;
use App\Actions\Households\CreateHouseholdAction;
use App\Actions\Households\ListUserHouseholdsAction;
use App\Actions\Households\TransferHouseholdOwnershipAction;
use App\DTO\Households\AddHouseholdMemberData;
use App\DTO\Households\CreateHouseholdData;
use App\DTO\Households\TransferHouseholdOwnershipData;
use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_household(): void
    {
        $user = User::factory()->create();
        $action = $this->app->make(CreateHouseholdAction::class);
        $data = new CreateHouseholdData(
            userId: $user->getKey(),
            name: 'Some Name idk',
        );

        $result = $action->handle($data);

        $this->assertSame('Some Name idk', $result->name);
        $this->assertSame(HouseholdRole::Owner, $result->role);
        $this->assertNotEmpty($result->uuid);

        $this->assertDatabaseHas('households', [
            'uuid' => $result->uuid,
            'name' => 'Some Name idk',
        ]);

        $household = Household::query()
            ->where('uuid', $result->uuid)
            ->firstOrFail();

        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $user->id,
            'role' => HouseholdRole::Owner->value,
        ]);
    }

    public function test_owner_can_transfer_household_ownership_to_member(): void
    {
        $household = Household::factory()->create();

        $oldOwner = User::factory()->create();
        $newOwner = User::factory()->create();

        HouseholdMembership::query()->create([
            'household_id' => $household->id,
            'user_id' => $oldOwner->id,
            'role' => HouseholdRole::Owner,
        ]);

        HouseholdMembership::query()->create([
            'household_id' => $household->id,
            'user_id' => $newOwner->id,
            'role' => HouseholdRole::Member,
        ]);

        $data = new TransferHouseholdOwnershipData(
            uuid: $household->uuid,
            currentOwnerUserId: $oldOwner->getKey(),
            newOwnerUserId: $newOwner->getKey(),
        );

        $action = $this->app->make(TransferHouseholdOwnershipAction::class);
        $result = $action->handle($data);

        $this->assertSame($household->uuid, $result->uuid);
        $this->assertSame($newOwner->getKey(), $result->newOwnerUserId);

        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $oldOwner->id,
            'role' => HouseholdRole::Member->value,
        ]);

        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $newOwner->id,
            'role' => HouseholdRole::Owner->value,
        ]);

        $this->assertSame(
            1,
            HouseholdMembership::query()
                ->where('household_id', $household->id)
                ->where('role', HouseholdRole::Owner)
                ->count(),
        );
    }

    public function test_non_owner_cannot_transfer_household_ownership(): void
    {
        $household = Household::factory()->create();

        $nonOwner = User::factory()->create();
        $alsoNonOwnerLol = User::factory()->create();

        HouseholdMembership::query()->create([
            'household_id' => $household->id,
            'user_id' => $nonOwner->id,
            'role' => HouseholdRole::Member,
        ]);

        HouseholdMembership::query()->create([
            'household_id' => $household->id,
            'user_id' => $alsoNonOwnerLol->id,
            'role' => HouseholdRole::Member,
        ]);

        $data = new TransferHouseholdOwnershipData(
            uuid: $household->uuid,
            currentOwnerUserId: $nonOwner->getKey(),
            newOwnerUserId: $alsoNonOwnerLol->getKey(),
        );

        $action = $this->app->make(TransferHouseholdOwnershipAction::class);

        $this->assertThrows(
            fn () => $action->handle($data),
            DomainException::class,
            'The current user is not the household owner.',
        );

        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $nonOwner->id,
            'role' => HouseholdRole::Member->value,
        ]);

        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $alsoNonOwnerLol->id,
            'role' => HouseholdRole::Member->value,
        ]);
    }

    public function test_ownership_cannot_be_transferred_to_user_outside_household(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        HouseholdMembership::query()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);

        $data = new TransferHouseholdOwnershipData(
            uuid: $household->uuid,
            currentOwnerUserId: $owner->getKey(),
            newOwnerUserId: $outsider->getKey(),
        );

        $action = $this->app->make(TransferHouseholdOwnershipAction::class);

        $this->assertThrows(
            fn () => $action->handle($data),
            ModelNotFoundException::class,
        );

        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner->value,
        ]);

        $this->assertDatabaseMissing('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $outsider->id,
        ]);
    }

    public function test_owner_cannot_transfer_household_ownership_to_themselves(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();

        HouseholdMembership::query()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);

        $data = new TransferHouseholdOwnershipData(
            uuid: $household->uuid,
            currentOwnerUserId: $owner->getKey(),
            newOwnerUserId: $owner->getKey(),
        );

        $action = $this->app->make(TransferHouseholdOwnershipAction::class);

        $this->assertThrows(
            fn () => $action->handle($data),
            DomainException::class,
            'The owner cannot transfer ownership to themselves.',
        );

        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner->value,
        ]);
    }

    public function test_owner_can_add_existing_user_to_household(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();
        $outsider = User::factory()->create();

        HouseholdMembership::query()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);

        $data = new AddHouseholdMemberData(
            uuid: $household->uuid,
            actorUserId: $owner->getKey(),
            newMemberEmail: $outsider->email,
        );

        $action = $this->app->make(AddHouseholdMemberAction::class);
        $result = $action->handle($data);

        $this->assertSame($household->uuid, $result->uuid);
        $this->assertSame($outsider->getKey(), $result->newMemberUserId);

        $this->assertDatabaseHas('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $outsider->getKey(),
            'role' => HouseholdRole::Member->value,
        ]);
    }

    public function test_non_owner_cannot_add_household_member(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();

        HouseholdMembership::query()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);

        HouseholdMembership::query()->create([
            'household_id' => $household->id,
            'user_id' => $member->id,
            'role' => HouseholdRole::Member,
        ]);

        $data = new AddHouseholdMemberData(
            uuid: $household->uuid,
            actorUserId: $member->getKey(),
            newMemberEmail: $outsider->email,
        );

        $action = $this->app->make(AddHouseholdMemberAction::class);

        $this->assertThrows(
            fn () => $action->handle($data),
            DomainException::class,
            'Only the household owner can add members.',
        );

        $this->assertDatabaseMissing('household_memberships', [
            'household_id' => $household->id,
            'user_id' => $outsider->id,
        ]);
    }

    public function test_owner_cannot_add_same_user_twice(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();

        HouseholdMembership::query()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);

        HouseholdMembership::query()->create([
            'household_id' => $household->id,
            'user_id' => $member->id,
            'role' => HouseholdRole::Member,
        ]);

        $data = new AddHouseholdMemberData(
            uuid: $household->uuid,
            actorUserId: $owner->getKey(),
            newMemberEmail: $member->email,
        );

        $action = $this->app->make(AddHouseholdMemberAction::class);

        $this->assertThrows(
            fn () => $action->handle($data),
            DomainException::class,
            'The user is already a household member.',
        );

        $this->assertSame(
            1,
            HouseholdMembership::query()
                ->where('household_id', $household->id)
                ->where('user_id', $member->id)
                ->count(),
        );
    }

    public function test_owner_cannot_add_unknown_user(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();

        HouseholdMembership::query()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);

        $data = new AddHouseholdMemberData(
            uuid: $household->uuid,
            actorUserId: $owner->getKey(),
            newMemberEmail: 'missing@example.org',
        );

        $action = $this->app->make(AddHouseholdMemberAction::class);

        $this->assertThrows(
            fn () => $action->handle($data),
            ModelNotFoundException::class,
        );

        $this->assertDatabaseCount('household_memberships', 1);
    }

    public function test_owner_cannot_add_themselves(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();

        HouseholdMembership::query()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);

        $data = new AddHouseholdMemberData(
            uuid: $household->uuid,
            actorUserId: $owner->getKey(),
            newMemberEmail: $owner->email,
        );

        $action = $this->app->make(AddHouseholdMemberAction::class);

        $this->assertThrows(
            fn () => $action->handle($data),
            DomainException::class,
            'The owner is already a household member.',
        );

        $this->assertSame(
            1,
            HouseholdMembership::query()
                ->where('household_id', $household->id)
                ->where('user_id', $owner->id)
                ->count(),
        );
    }

    public function test_user_can_list_only_their_household_memberships(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();
        $ownedHousehold = Household::factory()->create();
        $joinedHousehold = Household::factory()->create();
        $foreignHousehold = Household::factory()->create();

        HouseholdMembership::factory()->owner()->create([
            'household_id' => $ownedHousehold->id,
            'user_id' => $user->id,
        ]);
        HouseholdMembership::factory()->create([
            'household_id' => $joinedHousehold->id,
            'user_id' => $user->id,
        ]);
        HouseholdMembership::factory()->owner()->create([
            'household_id' => $foreignHousehold->id,
            'user_id' => $anotherUser->id,
        ]);

        $action = $this->app->make(ListUserHouseholdsAction::class);
        $memberships = $action->handle($user->getKey());

        $this->assertCount(2, $memberships);
        $this->assertEqualsCanonicalizing(
            [$ownedHousehold->id, $joinedHousehold->id],
            $memberships->pluck('household_id')->all(),
        );
        $this->assertTrue($memberships->every(
            fn (HouseholdMembership $membership) => $membership->relationLoaded('household'),
        ));
    }
}
