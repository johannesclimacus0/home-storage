<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\Models\HouseholdProduct;

final readonly class ShowHouseholdProductAction
{
    public function __construct(
        private HouseholdRepository $households,
        private InventoryRepository $inventory,
    ) {}

    public function handle(string $householdUuid, int $actorUserId, string $productUuid): HouseholdProduct
    {
        $household = $this->households->findByUuid($householdUuid);
        $this->households->findMembership($household, $actorUserId);

        return $this->inventory->findHouseholdProduct($household, $productUuid);
    }
}
