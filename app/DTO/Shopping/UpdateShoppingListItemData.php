<?php

namespace App\DTO\Shopping;

use App\Enums\MeasurementUnit;

final readonly class UpdateShoppingListItemData
{
    public function __construct(
        public string $householdUuid,
        public int $actorUserId,
        public string $itemUuid,
        public string $quantity,
        public MeasurementUnit $unit
    ) {}
}
