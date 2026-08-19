<?php

namespace App\DTO\Inventory;

final readonly class UpdateHouseholdProductData
{
    public function __construct(
        public string $householdUuid,
        public int $actorUserId,
        public string $productUuid,
        public string $lowStockThreshold,
    ) {}
}
