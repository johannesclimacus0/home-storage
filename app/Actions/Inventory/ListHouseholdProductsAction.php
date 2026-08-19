<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\Models\HouseholdProduct;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListHouseholdProductsAction
{
    public function __construct(
        private HouseholdRepository $households,
        private InventoryRepository $inventory,
    ) {}

    /** @return Collection<int, HouseholdProduct> */
    public function handle(string $householdUuid, int $actorUserId): Collection
    {
        $household = $this->households->findByUuid($householdUuid);
        $this->households->findMembership($household, $actorUserId);

        return $this->inventory->findHouseholdProducts($household);
    }
}
