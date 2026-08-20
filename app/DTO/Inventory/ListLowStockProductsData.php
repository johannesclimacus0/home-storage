<?php

namespace App\DTO\Inventory;

final readonly class ListLowStockProductsData
{
    public function __construct(
        public string $householdUuid,
        public int $actorUserId,
    ) {}
}
