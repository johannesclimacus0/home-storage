<?php

namespace App\Events\Inventory;

use Carbon\CarbonImmutable;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final class ProductRecoveredFromLowStock implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(
        public readonly int $householdProductId,
        public readonly string $totalQuantity,
        public readonly CarbonImmutable $occurredAt,
    ) {}
}
