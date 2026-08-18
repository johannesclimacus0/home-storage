<?php

namespace App\DTO\Households;

final readonly class AddHouseholdMemberResult
{
    public function __construct(
        public string $uuid,
        public int $newMemberUserId,
    )
    {
    }
}
