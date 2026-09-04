<?php

namespace App\Notifications\Telegram;

use App\Contracts\Notifications\SendsTelegramNotification;
use App\Enums\TelegramNotificationType;
use App\Notifications\Concerns\RoutesTelegramNotifications;
use App\Support\Telegram\TelegramMarkdown;
use DefStudio\Telegraph\Notifications\TelegraphMessage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

final class TelegramReminderNotification extends Notification implements SendsTelegramNotification, ShouldQueue
{
    use Queueable;
    use RoutesTelegramNotifications;

    public int $tries = 3;

    public function __construct(
        private readonly string $message
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return $this->withTelegramChannel($notifiable, []);
    }

    public function telegramType(): ?TelegramNotificationType
    {
        return null;
    }

    public function toTelegram(object $notifiable): TelegraphMessage
    {
        return TelegraphMessage::make(
            '*Silly reminder:*' . "\n" . TelegramMarkdown::escape($this->message)
        )->markdownV2();
    }
}
