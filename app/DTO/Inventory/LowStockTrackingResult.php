<?php

namespace App\DTO\Inventory;

final readonly class LowStockTrackingResult
{
    public function __construct(
        public string $totalQuantity,
        public bool $becameLowStock,
        public bool $recovered,
    ) {}
}
