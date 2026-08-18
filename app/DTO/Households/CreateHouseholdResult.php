<?php

namespace App\DTO\Households;

use App\Enums\HouseholdRole;

final readonly class CreateHouseholdResult
{
    public function __construct(
        public string $uuid,
        public string $name,
        public HouseholdRole $role,
    )
    {
    }
}
