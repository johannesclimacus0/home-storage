<?php

namespace App\Actions\Telegram;

use App\Exceptions\Telegram\InvalidTelegramLinkException;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

final class ConsumeTelegramLinkAction
{
    public function handle(string $token): User
    {
        if (strlen($token) !== 48) {
            throw new InvalidTelegramLinkException('Invalid telegram link');
        }

        $hash = hash('sha256', $token);
        $key = 'telegram-links:' . $hash;

        $userId = Cache::pull($key);

        if (is_string($userId) && ctype_digit($userId)) {
            $userId = (int) $userId;
        }

        if (!is_int($userId) || $userId < 1) {
            throw new InvalidTelegramLinkException('Invalid telegram link');
        }

        return User::query()->findOrFail($userId);
    }
}
