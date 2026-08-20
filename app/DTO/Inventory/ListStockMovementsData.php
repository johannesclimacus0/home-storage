<?php

namespace App\DTO\Inventory;

use App\Enums\StockMovementType;

final readonly class ListStockMovementsData
{
    public function __construct(
        public string $householdUuid,
        public int $actorUserId,
        public ?string $productUuid,
        public ?StockMovementType $type,
        public int $perPage,
    ) {}
}
