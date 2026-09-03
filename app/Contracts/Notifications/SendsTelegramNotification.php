<?php

namespace App\Contracts\Notifications;

use App\Enums\TelegramNotificationType;
use DefStudio\Telegraph\Notifications\TelegraphMessage;

interface SendsTelegramNotification
{
    public function telegramType(): ?TelegramNotificationType;

    public function toTelegram(object $notifiable): TelegraphMessage;
}
