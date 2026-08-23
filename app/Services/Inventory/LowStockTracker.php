<?php

namespace App\Services\Inventory;

use App\Contracts\Inventory\InventoryRepository;
use App\DTO\Inventory\LowStockTrackingResult;
use App\Events\Inventory\ProductBecameLowStock;
use App\Events\Inventory\ProductRecoveredFromLowStock;
use App\Models\HouseholdProduct;
use App\Support\Inventory\LowStockEvaluator;
use App\Support\Inventory\StockQuantity;
use Carbon\CarbonImmutable;

final readonly class LowStockTracker
{
    public function __construct(
        private InventoryRepository $inventory,
    ) {}

    public function refresh(HouseholdProduct $householdProduct, CarbonImmutable $now): LowStockTrackingResult
    {
        $totalQuantity = StockQuantity::databaseValue(
            $this->inventory->totalStockQuantity($householdProduct),
        );

        $wasLowStock = $householdProduct->low_stock_since !== null;

        $lowStockSince = LowStockEvaluator::resolveLowStockSince(
            totalQuantity: $totalQuantity,
            threshold: $householdProduct->low_stock_threshold,
            currentLowStockSince: $householdProduct->low_stock_since,
            now: $now,
        );

        $this->inventory->updateLowStockSince(
            $householdProduct,
            $lowStockSince,
        );

        $isLowStock = $lowStockSince !== null;

        $result = new LowStockTrackingResult(
            totalQuantity: $totalQuantity,
            becameLowStock: !$wasLowStock && $isLowStock,
            recovered: $wasLowStock && !$isLowStock,
        );

        if ($result->becameLowStock) {
            ProductBecameLowStock::dispatch(
                householdProductId: $householdProduct->getKey(),
                totalQuantity: $result->totalQuantity,
                occurredAt: $now,
            );
        }

        if ($result->recovered) {
            ProductRecoveredFromLowStock::dispatch(
                householdProductId: $householdProduct->getKey(),
                totalQuantity: $result->totalQuantity,
                occurredAt: $now,
            );
        }

        return $result;
    }
}
