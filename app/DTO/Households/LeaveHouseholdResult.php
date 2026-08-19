<?php

namespace App\DTO\Households;

final readonly class LeaveHouseholdResult
{
    public function __construct(
        public string $uuid,
        public bool $householdDeleted,
        public ?int $newOwnerUserId,
    ) {}
}
