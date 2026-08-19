<?php

namespace App\DTO\Households;

final readonly class UpdateHouseholdData
{
    public function __construct(
        public string $uuid,
        public int $actorUserId,
        public string $name,
    ) {}
}
