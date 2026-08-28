<?php

namespace App\DTO\Shopping;

final readonly class PurchaseShoppingListItemData
{
    public function __construct(
        public string $householdUuid,
        public int $actorUserId,
        public string $itemUuid,
        public string $storageLocationUuid
    ) {}
}
