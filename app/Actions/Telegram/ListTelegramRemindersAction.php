<?php

namespace App\Actions\Telegram;

use App\Models\TelegramReminder;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class ListTelegramRemindersAction
{
    /** @return Collection<int, TelegramReminder> */
    public function handle(User $user): Collection
    {
        return $user->telegramReminders()
            ->orderByRaw(
                'CASE WHEN dispatched_at IS NULL OR frequency IS NOT NULL THEN 0 ELSE 1 END'
            )
            ->orderBy('remind_at')
            ->get();
    }
}
