<?php

namespace Tests\Unit\Support\Inventory;

use App\Support\Inventory\LowStockEvaluator;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;

class LowStockEvaluatorTest extends TestCase
{
    public function test_quantity_below_threshold_is_low_stock(): void
    {
        $this->assertTrue(LowStockEvaluator::isLowStock('123.000', '1234.000'));
    }

    public function test_quantity_equal_to_threshold_is_low_stock(): void
    {
        $this->assertTrue(LowStockEvaluator::isLowStock('123.000', '123.000'));
    }

    public function test_quantity_above_threshold_is_not_low_stock(): void
    {
        $this->assertFalse(LowStockEvaluator::isLowStock('1234.000', '123.000'));
    }

    public function test_low_stock_since_follows_state_transitions(): void
    {
        $firstDetectedAt = CarbonImmutable::parse('2026-08-20 10:00:00');
        $later = CarbonImmutable::parse('2026-08-20 12:00:00');

        $normalState = LowStockEvaluator::resolveLowStockSince(
            totalQuantity: '1234.000',
            threshold: '123.000',
            currentLowStockSince: null,
            now: $firstDetectedAt,
        );

        $this->assertNull($normalState);

        $firstLowStockState = LowStockEvaluator::resolveLowStockSince(
            totalQuantity: '123.000',
            threshold: '1234.000',
            currentLowStockSince: null,
            now: $firstDetectedAt,
        );

        $this->assertSame($firstDetectedAt, $firstLowStockState);

        $stillLowStockState = LowStockEvaluator::resolveLowStockSince(
            totalQuantity: '123.000',
            threshold: '1234.000',
            currentLowStockSince: $firstLowStockState,
            now: $later,
        );

        $this->assertSame($firstDetectedAt, $stillLowStockState);

        $restoredState = LowStockEvaluator::resolveLowStockSince(
            totalQuantity: '1234.000',
            threshold: '123.000',
            currentLowStockSince: $stillLowStockState,
            now: $later,
        );

        $this->assertNull($restoredState);
    }
}
