<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\DTO\Inventory\ListLowStockProductsData;
use App\Models\HouseholdProduct;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListLowStockProductsAction
{
    public function __construct(
        private HouseholdRepository $households,
        private InventoryRepository $inventory,
    ) {}

    /** @return Collection<int, HouseholdProduct> */
    public function handle(ListLowStockProductsData $data): Collection
    {
        $household = $this->households->findByUuid($data->householdUuid);
        $this->households->findMembership($household, $data->actorUserId);

        return $this->inventory->findLowStockProducts($household);
    }
}
