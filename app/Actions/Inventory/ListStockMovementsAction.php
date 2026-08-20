<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\StockMovementRepository;
use App\DTO\Inventory\ListStockMovementsData;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

final readonly class ListStockMovementsAction
{
    public function __construct(
        private HouseholdRepository $households,
        private StockMovementRepository $movements
    ) {}

    public function handle(ListStockMovementsData $data): LengthAwarePaginator
    {
        $household = $this->households->findByUuid($data->householdUuid);
        $this->households->findMembership($household, $data->actorUserId);

        return $this->movements->paginateForHousehold(
            household: $household,
            productUuid: $data->productUuid,
            type: $data->type,
            perPage: $data->perPage,
        );
    }
}
