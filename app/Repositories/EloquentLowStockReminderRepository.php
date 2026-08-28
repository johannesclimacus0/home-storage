<?php

namespace App\Repositories;

use App\Contracts\Inventory\LowStockReminderRepository;
use App\DTO\Inventory\LowStockReminderCandidate;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\LowStockNotificationState;
use App\Models\User;
use App\Support\Inventory\StockQuantity;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class EloquentLowStockReminderRepository implements LowStockReminderRepository
{
    public function dueAt(CarbonImmutable $now): Collection
    {
        $products = HouseholdProduct::query()
            ->whereNotNull('low_stock_since')
            ->with([
                'product',
                'household',
                'household.householdMemberships' => fn ($query) => $query
                    ->where('low_stock_reminders_enabled', true)
                    ->with('user'),
                'lowStockNotificationStates',
            ])
            ->withSum('stocks', 'quantity')
            ->get();

        return $products->flatMap(function (HouseholdProduct $householdProduct) use ($now): array {
            $candidates = [];

            foreach ($householdProduct->household->householdMemberships as $membership) {
                $state = $householdProduct->lowStockNotificationStates
                    ->firstWhere('household_membership_id', $membership->getKey());
                $dueBefore = $now->subHours($membership->low_stock_reminder_interval_hours);

                if ($state !== null && $state->last_notified_at->isAfter($dueBefore)) {
                    continue;
                }

                $candidates[] = new LowStockReminderCandidate(
                    membership: $membership,
                    householdProduct: $householdProduct,
                    totalQuantity: StockQuantity::databaseValue(
                        (string) ($householdProduct->stocks_sum_quantity ?? '0')
                    )
                );
            }

            return $candidates;
        })->values();
    }

    public function markDispatched(
        HouseholdMembership $membership,
        HouseholdProduct $householdProduct,
        CarbonImmutable $at
    ): void {
        LowStockNotificationState::query()->updateOrCreate(
            [
                'household_membership_id' => $membership->getKey(),
                'household_product_id' => $householdProduct->getKey(),
            ],
            ['last_notified_at' => $at]
        );
    }

    public function findMembership(Household $household, User $user): HouseholdMembership
    {
        return HouseholdMembership::query()
            ->where('household_id', $household->getKey())
            ->where('user_id', $user->getKey())
            ->firstOrFail();
    }

    public function updateSettings(
        HouseholdMembership $membership,
        bool $enabled,
        int $intervalHours
    ): HouseholdMembership {
        $membership->updateOrFail([
            'low_stock_reminders_enabled' => $enabled,
            'low_stock_reminder_interval_hours' => $intervalHours,
        ]);

        return $membership->refresh();
    }
}
