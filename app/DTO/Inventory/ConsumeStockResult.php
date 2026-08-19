<?php

namespace App\DTO\Inventory;

use App\Enums\MeasurementUnit;

final readonly class ConsumeStockResult
{
    public function __construct(
        public string $householdUuid,
        public string $productUuid,
        public string $storageLocationUuid,
        public string $consumedQuantity,
        public MeasurementUnit $unit,
        public string $locationQuantity,
        public string $totalQuantity,
    ) {}
}
