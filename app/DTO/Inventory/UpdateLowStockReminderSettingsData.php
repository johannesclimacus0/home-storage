<?php

namespace App\DTO\Inventory;

use App\Models\Household;
use App\Models\User;

final readonly class UpdateLowStockReminderSettingsData
{
    public function __construct(
        public Household $household,
        public User $user,
        public bool $enabled,
        public int $intervalHours
    ) {}
}
