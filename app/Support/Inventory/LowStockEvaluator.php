<?php

namespace App\Support\Inventory;

use Carbon\CarbonImmutable;

final class LowStockEvaluator
{
    public static function isLowStock(string $totalQuantity, string $threshold): bool
    {
        return StockQuantity::toMinorUnits($totalQuantity) <= StockQuantity::toMinorUnits($threshold);
    }

    public static function resolveLowStockSince(
        string $totalQuantity,
        string $threshold,
        ?CarbonImmutable $currentLowStockSince,
        CarbonImmutable $now
    ): ?CarbonImmutable {
        if (!self::isLowStock($totalQuantity, $threshold)) {
            return null;
        }

        if ($currentLowStockSince !== null) {
            return $currentLowStockSince;
        }

        return $now;
    }
}
