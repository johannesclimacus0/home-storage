<?php

namespace App\Support\Inventory;

use App\Enums\MeasurementType;
use App\Enums\MeasurementUnit;
use App\Exceptions\Inventory\InvalidStockQuantity;

final class StockQuantity
{
    public static function toBaseUnit(
        string $value,
        MeasurementUnit $unit,
        MeasurementType $measurementType,
    ): string {
        if ($unit->measurementType() !== $measurementType) {
            throw new InvalidStockQuantity(
                __('messages.inventory.unit_incompatible'),
                'unit',
            );
        }

        [$whole, $fraction] = self::parts($value);

        if ($measurementType === MeasurementType::Count && trim($fraction, '0') !== '') {
            throw new InvalidStockQuantity(__('messages.inventory.quantity_whole'));
        }

        if ($unit->isLargeUnit()) {
            $whole = ltrim($whole . $fraction, '0');
            $whole = $whole === '' ? '0' : $whole;
            $fraction = '000';
        }

        if (strlen($whole) > 11) {
            throw new InvalidStockQuantity(__('messages.inventory.quantity_too_large'));
        }

        return $whole . '.' . $fraction;
    }

    public static function databaseValue(string $value): string
    {
        [$whole, $fraction] = self::parts($value);

        return $whole . '.' . $fraction;
    }

    public static function toMinorUnits(string $value): int
    {
        [$whole, $fraction] = explode('.', self::databaseValue($value));

        return (int) ($whole . $fraction);
    }

    private static function parts(string $value): array
    {
        $value = trim($value);

        if ($value === '' || !is_numeric($value) || str_contains(strtolower($value), 'e')) {
            throw new InvalidStockQuantity(__('messages.inventory.quantity_format'));
        }

        if (str_starts_with($value, '-')) {
            throw new InvalidStockQuantity(__('messages.inventory.quantity_negative'));
        }

        $value = ltrim($value, '+');
        [$whole, $fraction] = array_pad(explode('.', $value, 2), 2, '');

        if (strlen($fraction) > 3) {
            throw new InvalidStockQuantity(__('messages.inventory.quantity_format'));
        }

        $whole = ltrim($whole, '0');
        $whole = $whole === '' ? '0' : $whole;

        return [$whole, str_pad($fraction, 3, '0')];
    }
}
