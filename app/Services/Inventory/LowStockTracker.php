<?php

namespace App\Services\Inventory;

use App\Contracts\Inventory\InventoryRepository;
use App\Models\HouseholdProduct;
use App\Support\Inventory\LowStockEvaluator;
use App\Support\Inventory\StockQuantity;
use Carbon\CarbonImmutable;

final readonly class LowStockTracker
{
    public function __construct(
        private InventoryRepository $inventory,
    ) {}

    public function refresh(HouseholdProduct $householdProduct, CarbonImmutable $now): string
    {
        $totalQuantity = StockQuantity::databaseValue(
            $this->inventory->totalStockQuantity($householdProduct),
        );

        $lowStockSince = LowStockEvaluator::resolveLowStockSince(
            totalQuantity: $totalQuantity,
            threshold: $householdProduct->low_stock_threshold,
            currentLowStockSince: $householdProduct->low_stock_since,
            now: $now,
        );

        $this->inventory->updateLowStockSince($householdProduct, $lowStockSince);

        return $totalQuantity;
    }
}
