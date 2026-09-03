<?php

namespace App\Notifications\Channels;

use App\Contracts\Notifications\SendsTelegramNotification;
use App\Models\User;
use App\Services\Telegram\TelegramDeliveryPolicy;
use Illuminate\Notifications\Notification;
use LogicException;

final readonly class TelegramChannel
{
    public function __construct(
        private TelegramDeliveryPolicy $deliveryPolicy
    ) {}

    public function send(object $notifiable, Notification $notification): void
    {
        if (!$notification instanceof SendsTelegramNotification) {
            throw new LogicException($notification::class . ' must implement ' . SendsTelegramNotification::class);
        }

        if (!$notifiable instanceof User || !$this->deliveryPolicy->allows($notifiable, $notification)) {
            return;
        }

        $chat = $notifiable->telegramConnection()
            ->with('chat')
            ->first()
            ?->chat;

        if ($chat === null) {
            return;
        }

        $notification->toTelegram($notifiable)
            ->chat($chat)
            ->send();
    }
}
