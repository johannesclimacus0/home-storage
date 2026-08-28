<?php

namespace App\DTO\Shopping;

use App\Enums\MeasurementUnit;

final readonly class AddShoppingListItemData
{
    public function __construct(
        public string $householdUuid,
        public int $actorUserId,
        public string $productUuid,
        public string $quantity,
        public MeasurementUnit $unit
    ) {}
}
