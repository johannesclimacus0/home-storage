<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\Models\StorageLocation;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListStorageLocationsAction
{
    public function __construct(
        private HouseholdRepository $households,
        private InventoryRepository $inventory,
    ) {}

    /** @return Collection<int, StorageLocation> */
    public function handle(string $householdUuid, int $actorUserId): Collection
    {
        $household = $this->households->findByUuid($householdUuid);
        $this->households->findMembership($household, $actorUserId);

        return $this->inventory->findStorageLocations($household);
    }
}
