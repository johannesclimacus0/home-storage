<?php

namespace App\Actions\Telegram;

use App\Models\User;

final class DisconnectTelegramAccountAction
{
    public function handle(User $user): void
    {
        $user->telegramConnection()->delete();
    }
}
