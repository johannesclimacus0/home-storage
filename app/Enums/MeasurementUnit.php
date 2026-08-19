<?php

namespace App\Enums;

enum MeasurementUnit: string
{
    case Gram = 'g';
    case Kilogram = 'kg';
    case Milliliter = 'ml';
    case Liter = 'l';
    case Piece = 'piece';

    public function measurementType(): MeasurementType
    {
        return match ($this) {
            self::Gram, self::Kilogram => MeasurementType::Mass,
            self::Milliliter, self::Liter => MeasurementType::Volume,
            self::Piece => MeasurementType::Count,
        };
    }

    public function isLargeUnit(): bool
    {
        return $this === self::Kilogram || $this === self::Liter;
    }

    public function baseUnit(): self
    {
        return match ($this) {
            self::Kilogram => self::Gram,
            self::Liter => self::Milliliter,
            default => $this,
        };
    }
}
