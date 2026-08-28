<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\CreateStorageLocationAction;
use App\DTO\Inventory\CreateStorageLocationData;
use App\Enums\HouseholdRole;
use App\Exceptions\Inventory\StorageLocationConflict;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class StorageLocationActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_household_member_can_create_storage_location(): void
    {
        [$household, $member] = $this->householdWithMember();

        $result = $this->action()->handle(new CreateStorageLocationData(
            householdUuid: $household->uuid,
            actorUserId: $member->id,
            name: 'Fridge',
        ));

        $this->assertSame($household->uuid, $result->householdUuid);
        $this->assertSame('Fridge', $result->name);
        $this->assertNotEmpty($result->locationUuid);
        $this->assertDatabaseHas('storage_locations', [
            'uuid' => $result->locationUuid,
            'household_id' => $household->id,
            'name' => 'Fridge',
        ]);
    }

    public function test_storage_location_name_is_trimmed(): void
    {
        [$household, $member] = $this->householdWithMember();

        $result = $this->action()->handle(new CreateStorageLocationData(
            householdUuid: $household->uuid,
            actorUserId: $member->id,
            name: '  Freezer  ',
        ));

        $this->assertSame('Freezer', $result->name);
    }

    public function test_outsider_cannot_create_storage_location(): void
    {
        $household = Household::factory()->create();
        $outsider = User::factory()->create();

        $this->assertThrows(
            fn () => $this->action()->handle(new CreateStorageLocationData(
                householdUuid: $household->uuid,
                actorUserId: $outsider->id,
                name: 'Fridge',
            )),
            ModelNotFoundException::class,
        );

        $this->assertDatabaseCount('storage_locations', 0);
    }

    public function test_duplicate_storage_location_cannot_be_created(): void
    {
        [$household, $member] = $this->householdWithMember();
        StorageLocation::factory()->create([
            'household_id' => $household->id,
            'name' => 'Fridge',
        ]);

        $this->assertThrows(
            fn () => $this->action()->handle(new CreateStorageLocationData(
                householdUuid: $household->uuid,
                actorUserId: $member->id,
                name: 'Fridge',
            )),
            StorageLocationConflict::class,
            'A storage location with this name already exists.',
        );

        $this->assertDatabaseCount('storage_locations', 1);
    }

    public function test_empty_storage_location_name_is_rejected(): void
    {
        [$household, $member] = $this->householdWithMember();

        $this->assertThrows(
            fn () => $this->action()->handle(new CreateStorageLocationData(
                householdUuid: $household->uuid,
                actorUserId: $member->id,
                name: '   ',
            )),
            InvalidArgumentException::class,
            'Название места хранения не может быть пустым.',
        );

        $this->assertDatabaseCount('storage_locations', 0);
    }

    private function action(): CreateStorageLocationAction
    {
        return $this->app->make(CreateStorageLocationAction::class);
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
