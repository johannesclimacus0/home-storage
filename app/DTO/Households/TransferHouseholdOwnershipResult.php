<?php

namespace App\DTO\Households;

final readonly class TransferHouseholdOwnershipResult
{
    public function __construct(
        public string $uuid,
        public int $newOwnerUserId,
    )
    {
    }
}
