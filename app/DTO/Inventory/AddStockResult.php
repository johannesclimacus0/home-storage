<?php

namespace App\DTO\Inventory;

use App\Enums\MeasurementUnit;

final readonly class AddStockResult
{
    public function __construct(
        public string $householdUuid,
        public string $productUuid,
        public string $storageLocationUuid,
        public string $addedQuantity,
        public MeasurementUnit $unit,
        public string $locationQuantity,
        public string $totalQuantity,
    ) {}
}
