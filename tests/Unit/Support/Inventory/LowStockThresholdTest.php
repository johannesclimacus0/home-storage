<?php

namespace Tests\Unit\Support\Inventory;

use App\Enums\MeasurementType;
use App\Exceptions\Inventory\InvalidLowStockThreshold;
use App\Support\Inventory\LowStockThreshold;
use Tests\TestCase;

final class LowStockThresholdTest extends TestCase
{
    public function test_it_normalizes_threshold_to_database_precision(): void
    {
        $this->assertSame('0.000', LowStockThreshold::normalize('0', MeasurementType::Mass));
        $this->assertSame('12.300', LowStockThreshold::normalize('0012.3', MeasurementType::Volume));
        $this->assertSame('2.000', LowStockThreshold::normalize('+2', MeasurementType::Count));
    }

    public function test_count_threshold_must_be_whole(): void
    {
        $this->expectException(InvalidLowStockThreshold::class);

        LowStockThreshold::normalize('1.5', MeasurementType::Count);
    }

    public function test_invalid_negative_precise_and_oversized_thresholds_are_rejected(): void
    {
        foreach (['-1', '1.2345', '1e3', '100000000000'] as $threshold) {
            try {
                LowStockThreshold::normalize($threshold, MeasurementType::Mass);
                $this->fail("Threshold {$threshold} was accepted.");
            } catch (InvalidLowStockThreshold) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
