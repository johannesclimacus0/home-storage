<?php

declare(strict_types=1);

namespace App\DTO\Inventory;

final readonly class AddProductToHouseholdData
{
    public function __construct(
        public string $householdUuid,
        public int $actorUserId,
        public string $productUuid,
        public string $lowStockThreshold,
    ) {}
}
