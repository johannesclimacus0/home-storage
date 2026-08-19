<?php

namespace App\Support\Inventory;

use App\Enums\MeasurementType;
use App\Exceptions\Inventory\InvalidLowStockThreshold;

final class LowStockThreshold
{
    public static function normalize(string $value, MeasurementType $measurementType): string
    {
        $value = trim($value);

        if ($value === '' || !is_numeric($value) || str_contains(strtolower($value), 'e')) {
            throw new InvalidLowStockThreshold(
                'The low stock threshold must be a decimal with at most three decimal places.',
            );
        }

        if (str_starts_with($value, '-')) {
            throw new InvalidLowStockThreshold('The low stock threshold cannot be negative.');
        }

        $value = ltrim($value, '+');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        if (strlen($fraction) > 3) {
            throw new InvalidLowStockThreshold(
                'The low stock threshold must be a decimal with at most three decimal places.',
            );
        }

        if ($measurementType === MeasurementType::Count && trim($fraction, '0') !== '') {
            throw new InvalidLowStockThreshold(
                'Countable products require a whole-number threshold.',
            );
        }

        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;

        if (strlen($whole) > 11) {
            throw new InvalidLowStockThreshold('The low stock threshold is too big.');
        }

        return $whole . '.' . str_pad($fraction, 3, '0');
    }
}
