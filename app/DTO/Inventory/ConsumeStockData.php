<?php

namespace App\DTO\Inventory;

use App\Enums\MeasurementUnit;

final readonly class ConsumeStockData
{
    public function __construct(
        public string $householdUuid,
        public int $actorUserId,
        public string $productUuid,
        public string $storageLocationUuid,
        public string $quantity,
        public MeasurementUnit $unit,
    ) {}
}
