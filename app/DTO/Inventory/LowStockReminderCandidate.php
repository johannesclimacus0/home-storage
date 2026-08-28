<?php

namespace App\DTO\Inventory;

use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;

final readonly class LowStockReminderCandidate
{
    public function __construct(
        public HouseholdMembership $membership,
        public HouseholdProduct $householdProduct,
        public string $totalQuantity
    ) {}
}
