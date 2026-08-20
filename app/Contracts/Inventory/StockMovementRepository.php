<?php

namespace App\Contracts\Inventory;

use App\DTO\Inventory\CreateStockMovementData;
use App\Enums\StockMovementType;
use App\Models\Household;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface StockMovementRepository
{
    public function create(CreateStockMovementData $data): StockMovement;

    public function paginateForHousehold(
        Household $household,
        ?string $productUuid,
        ?StockMovementType $type,
        int $perPage,
    ): LengthAwarePaginator;
}
