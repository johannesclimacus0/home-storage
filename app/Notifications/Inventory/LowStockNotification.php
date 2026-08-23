<?php

namespace App\Notifications\Inventory;

use Carbon\CarbonImmutable;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $householdUuid,
        public readonly string $householdName,
        public readonly string $productUuid,
        public readonly string $productName,
        public readonly string $measurementType,
        public readonly string $totalQuantity,
        public readonly string $lowStockThreshold,
        public readonly CarbonImmutable $occurredAt,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return [
            'database',
            'broadcast',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'household_uuid' => $this->householdUuid,
            'household_name' => $this->householdName,
            'product_uuid' => $this->productUuid,
            'product_name' => $this->productName,
            'measurement_type' => $this->measurementType,
            'total_quantity' => $this->totalQuantity,
            'low_stock_threshold' => $this->lowStockThreshold,
            'occurred_at' => $this->occurredAt->toISOString(),
        ];
    }
}
