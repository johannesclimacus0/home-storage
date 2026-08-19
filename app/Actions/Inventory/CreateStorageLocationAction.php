<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\DTO\Inventory\CreateStorageLocationData;
use App\DTO\Inventory\CreateStorageLocationResult;
use App\Exceptions\Inventory\StorageLocationConflict;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class CreateStorageLocationAction
{
    public function __construct(
        private InventoryRepository $inventory,
        private HouseholdRepository $households,
    ) {}

    public function handle(CreateStorageLocationData $data): CreateStorageLocationResult
    {
        return DB::transaction(function () use ($data): CreateStorageLocationResult {
            $name = trim($data->name);

            if ($name === '') {
                throw new InvalidArgumentException('The storage location name cannot be empty.');
            }

            $household = $this->households->findByUuidForUpdate($data->householdUuid);

            $this->households->findMembershipForUpdate(
                $household,
                $data->actorUserId,
            );

            if ($this->inventory->storageLocationExists($household, $name)) {
                throw new StorageLocationConflict(
                    'A storage location with this name already exists.',
                );
            }

            $location = $this->inventory->createStorageLocation($household, $name);

            return new CreateStorageLocationResult(
                householdUuid: $household->uuid,
                locationUuid: $location->uuid,
                name: $location->name,
            );
        });
    }
}
