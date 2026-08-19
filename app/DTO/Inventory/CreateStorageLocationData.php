<?php

namespace App\DTO\Inventory;

final readonly class CreateStorageLocationData
{
    public function __construct(
        public string $householdUuid,
        public int $actorUserId,
        public string $name,
    ) {}
}
