<?php

namespace App\Services\Telegram;

use Illuminate\Support\Facades\Cache;

final class TelegramConversationState
{
    /** @param array<string, int|string> $state */
    public function put(string $chatId, array $state): void
    {
        Cache::put($this->key($chatId), $state, now()->addMinutes(15));
    }

    /** @return array<string, int|string>|null */
    public function get(string $chatId): ?array
    {
        $state = Cache::get($this->key($chatId));

        return is_array($state) ? $state : null;
    }

    public function forget(string $chatId): void
    {
        Cache::forget($this->key($chatId));
    }

    private function key(string $chatId): string
    {
        return 'telegram:conversation:' . $chatId;
    }
}
