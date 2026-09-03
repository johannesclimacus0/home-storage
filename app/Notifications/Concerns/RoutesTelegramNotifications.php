<?php

namespace App\Notifications\Concerns;

use App\Contracts\Notifications\SendsTelegramNotification;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Services\Telegram\TelegramDeliveryPolicy;

trait RoutesTelegramNotifications
{
    /**
     * @param  array<int, string>  $channels
     * @return array<int, string>
     */
    protected function withTelegramChannel(object $notifiable, array $channels): array
    {
        if ($notifiable instanceof User
            && $this instanceof SendsTelegramNotification
            && app(TelegramDeliveryPolicy::class)->allows($notifiable, $this)) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }
}
