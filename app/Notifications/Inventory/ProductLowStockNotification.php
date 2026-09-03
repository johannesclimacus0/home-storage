<?php

namespace App\Notifications\Inventory;

use App\Contracts\Notifications\SendsTelegramNotification;
use App\Enums\MeasurementType;
use App\Enums\TelegramNotificationType;
use App\Notifications\Concerns\RoutesTelegramNotifications;
use App\Support\Telegram\TelegramMarkdown;
use Carbon\CarbonImmutable;
use DefStudio\Telegraph\Notifications\TelegraphMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ProductLowStockNotification extends Notification implements SendsTelegramNotification, ShouldQueue
{
    use Queueable;
    use RoutesTelegramNotifications;

    public int $tries = 3;

    public int $timeout = 15;

    public bool $failOnTimeout = true;

    public function __construct(
        private string $householdUuid,
        private string $householdName,
        private string $productUuid,
        private string $productName,
        private MeasurementType $measurementType,
        private string $totalQuantity,
        private string $threshold,
        private CarbonImmutable $becameLowAt
    ) {
        $this->onQueue('notifications');
    }

    public function backoff(): array
    {
        return [10, 60];
    }

    public function via(object $notifiable): array
    {
        return $this->withTelegramChannel($notifiable, ['database', 'broadcast']);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'household_uuid' => $this->householdUuid,
            'household_name' => $this->householdName,
            'product_uuid' => $this->productUuid,
            'product_name' => $this->productName,
            'measurement_type' => $this->measurementType->value,
            'quantity' => $this->totalQuantity,
            'threshold' => $this->threshold,
            'became_low_at' => $this->becameLowAt->toIso8601String(),
        ];
    }

    public function telegramType(): TelegramNotificationType
    {
        return TelegramNotificationType::LowStock;
    }

    public function toTelegram(object $notifiable): TelegraphMessage
    {
        $message = '*Заканчивается продукт*' . "\n\n"
            . '*' . TelegramMarkdown::escape($this->productName) . '*' . "\n"
            . 'Дом: ' . TelegramMarkdown::escape($this->householdName) . "\n"
            . 'Осталось: ' . TelegramMarkdown::escape($this->totalQuantity) . "\n"
            . 'Порог: ' . TelegramMarkdown::escape($this->threshold);

        return TelegraphMessage::make($message)->markdownV2();
    }
}
