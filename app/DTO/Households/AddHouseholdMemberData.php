<?php

namespace App\DTO\Households;

final readonly class AddHouseholdMemberData
{
    public function __construct(
        public string $uuid,
        public int $actorUserId,
        public string $newMemberEmail,
    ) {}
}
