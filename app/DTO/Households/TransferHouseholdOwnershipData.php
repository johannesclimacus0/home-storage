<?php

namespace App\DTO\Households;

final readonly class TransferHouseholdOwnershipData
{
    public function __construct(
        public string $uuid,
        public int $currentOwnerUserId,
        public int $newOwnerUserId,
    )
    {
    }
}
