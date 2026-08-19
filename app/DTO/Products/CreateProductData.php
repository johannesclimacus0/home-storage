<?php

namespace App\DTO\Products;

use App\Enums\MeasurementType;

final readonly class CreateProductData
{
    public function __construct(
        public string $name,
        public MeasurementType $measurementType,
    ) {}
}
