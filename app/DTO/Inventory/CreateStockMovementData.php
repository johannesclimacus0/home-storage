<?php

namespace App\DTO\Inventory;

use App\Enums\MeasurementUnit;
use App\Enums\StockMovementType;

final readonly class CreateStockMovementData
{
    public function __construct(
        public int $householdId,
        public ?int $householdProductId,
        public int $productId,
        public ?int $storageLocationId,
        public ?int $actorUserId,
        public StockMovementType $type,
        public string $inputQuantity,
        public MeasurementUnit $inputUnit,
        public string $quantityDelta,
        public string $quantityBefore,
        public string $quantityAfter,
        public string $productName,
        public string $storageLocationName,
        public string $actorName,
    ) {}
}
