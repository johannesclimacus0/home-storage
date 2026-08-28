<?php

namespace App\Contracts\Inventory;

use App\DTO\Inventory\LowStockReminderCandidate;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

interface LowStockReminderRepository
{
    /** @return Collection<int, LowStockReminderCandidate> */
    public function dueAt(CarbonImmutable $now): Collection;

    public function markDispatched(
        HouseholdMembership $membership,
        HouseholdProduct $householdProduct,
        CarbonImmutable $at
    ): void;

    public function findMembership(Household $household, User $user): HouseholdMembership;

    public function updateSettings(
        HouseholdMembership $membership,
        bool $enabled,
        int $intervalHours
    ): HouseholdMembership;
}
