<?php

namespace App\DTO\Inventory;

use App\Enums\MeasurementType;

final readonly class AddProductToHouseholdResult
{
    public function __construct(
        public string $householdUuid,
        public string $productUuid,
        public string $productName,
        public MeasurementType $measurementType,
        public string $lowStockThreshold,
    ) {}
}
