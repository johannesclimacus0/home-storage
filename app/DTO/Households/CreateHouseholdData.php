<?php

namespace App\DTO\Households;

final readonly class CreateHouseholdData
{
    public function __construct(
        public int $userId,
        public string $name,
    )
    {
    }
}
