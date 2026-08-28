<?php

namespace Database\Factories;

use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\LowStockNotificationState;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<LowStockNotificationState> */
class LowStockNotificationStateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'household_membership_id' => HouseholdMembership::factory(),
            'household_product_id' => HouseholdProduct::factory(),
            'last_notified_at' => now(),
        ];
    }
}
