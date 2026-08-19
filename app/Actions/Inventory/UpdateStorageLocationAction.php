<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\DTO\Inventory\UpdateStorageLocationData;
use App\Exceptions\Inventory\StorageLocationConflict;
use App\Models\StorageLocation;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateStorageLocationAction
{
    public function __construct(
        private HouseholdRepository $households,
        private InventoryRepository $inventory,
    ) {}

    public function handle(UpdateStorageLocationData $data): StorageLocation
    {
        return DB::transaction(function () use ($data): StorageLocation {
            $name = trim($data->name);
            if ($name === '') {
                throw new InvalidArgumentException('The storage location name cannot be empty.');
            }

            $household = $this->households->findByUuidForUpdate($data->householdUuid);
            $this->households->findMembershipForUpdate($household, $data->actorUserId);
            $location = $this->inventory->findStorageLocationForUpdate(
                $household,
                $data->locationUuid,
            );

            if ($this->inventory->storageLocationExists($household, $name, $location)) {
                throw new StorageLocationConflict(
                    'A storage location with this name already exists.',
                );
            }

            $this->inventory->updateStorageLocation($location, $name);

            return $this->inventory->findStorageLocation($household, $location->uuid);
        });
    }
}
