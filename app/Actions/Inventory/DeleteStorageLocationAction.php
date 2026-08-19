<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\Exceptions\Inventory\StorageLocationConflict;
use Illuminate\Support\Facades\DB;

final readonly class DeleteStorageLocationAction
{
    public function __construct(
        private HouseholdRepository $households,
        private InventoryRepository $inventory,
    ) {}

    public function handle(string $householdUuid, int $actorUserId, string $locationUuid): void
    {
        DB::transaction(function () use ($householdUuid, $actorUserId, $locationUuid): void {
            $household = $this->households->findByUuidForUpdate($householdUuid);
            $this->households->findMembershipForUpdate($household, $actorUserId);
            $location = $this->inventory->findStorageLocationForUpdate(
                $household,
                $locationUuid,
            );

            if ($this->inventory->storageLocationHasStock($location)) {
                throw new StorageLocationConflict(
                    'Storage location contains stock and cannot be deleted.',
                );
            }

            $this->inventory->deleteStorageLocation($location);
        });
    }
}
