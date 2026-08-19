<?php

namespace App\DTO\Households;

final readonly class RemoveHouseholdMemberData
{
    public function __construct(
        public string $uuid,
        public int $actorUserId,
        public int $memberUserId,
    ) {}
}
