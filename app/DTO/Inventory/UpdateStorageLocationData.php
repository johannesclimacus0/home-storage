<?php

namespace App\DTO\Inventory;

final readonly class UpdateStorageLocationData
{
    public function __construct(
        public string $householdUuid,
        public int $actorUserId,
        public string $locationUuid,
        public string $name,
    ) {}
}
