<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\Models\StorageLocation;

final readonly class ShowStorageLocationAction
{
    public function __construct(
        private HouseholdRepository $households,
        private InventoryRepository $inventory,
    ) {}

    public function handle(string $householdUuid, int $actorUserId, string $locationUuid): StorageLocation {
        $household = $this->households->findByUuid($householdUuid);
        $this->households->findMembership($household, $actorUserId);

        return $this->inventory->findStorageLocation($household, $locationUuid);
    }
}
