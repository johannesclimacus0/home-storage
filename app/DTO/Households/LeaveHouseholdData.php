<?php

namespace App\DTO\Households;

final readonly class LeaveHouseholdData
{
    public function __construct(
        public string $uuid,
        public int $actorUserId,
    ) {}
}
