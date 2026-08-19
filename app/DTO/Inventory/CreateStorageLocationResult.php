<?php

declare(strict_types=1);

namespace App\DTO\Inventory;

final readonly class CreateStorageLocationResult
{
    public function __construct(
        public string $householdUuid,
        public string $locationUuid,
        public string $name,
    ) {}
}
