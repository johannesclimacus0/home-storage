<?php

namespace App\Services\Telegram;

use App\Contracts\Notifications\SendsTelegramNotification;
use App\Models\User;

class TelegramDeliveryPolicy
{
    public function allows(User $user, SendsTelegramNotification $notification): bool
    {
        if (!$user->telegramConnection()->exists()) {
            return false;
        }

        $type = $notification->telegramType();

        if ($type === null) {
            return true;
        }

        return $user->telegramNotificationSubscriptions()
            ->where('type', $type->value)
            ->exists();
    }
}
