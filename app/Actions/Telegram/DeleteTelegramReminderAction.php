<?php

namespace App\Actions\Telegram;

use App\Models\User;

final class DeleteTelegramReminderAction
{
    public function handle(User $user, string $reminderUuid): void
    {
        $user->telegramReminders()
            ->where('uuid', $reminderUuid)
            ->firstOrFail()
            ->deleteOrFail();
    }
}
