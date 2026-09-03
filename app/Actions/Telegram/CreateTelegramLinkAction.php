<?php

namespace App\Actions\Telegram;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;

class CreateTelegramLinkAction
{
    public function handle(User $user): string
    {
        $botUsername = config('services.telegram.bot_username');

        if (!is_string($botUsername) || $botUsername === '') {
            throw new RuntimeException('Telegram bot username is not configured.');
        }

        $token = Str::random(48);
        $hash = hash('sha256', $token);
        $key = 'telegram-links:' . $hash;

        Cache::put($key, $user->getKey(), now()->addMinutes(10));

        $botUsername = ltrim(trim($botUsername), '@');

        return 'https://t.me/' . $botUsername . '?start=' . rawurlencode($token);
    }
}
