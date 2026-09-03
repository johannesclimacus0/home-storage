<?php

namespace App\Actions\Telegram;

use App\Enums\TelegramReminderFrequency;
use App\Models\TelegramReminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class UpdateTelegramReminderAction
{
    public function handle(
        User $user,
        string $reminderUuid,
        string $message,
        CarbonImmutable $remindAt,
        ?TelegramReminderFrequency $frequency
    ): TelegramReminder {
        $reminder = $user->telegramReminders()
            ->where('uuid', $reminderUuid)
            ->firstOrFail();

        if ($reminder->dispatched_at !== null && $reminder->frequency === null) {
            throw ValidationException::withMessages([
                'reminder' => 'Отправленное напоминание нельзя изменить.',
            ]);
        }

        $reminder->update([
            'message' => $message,
            'remind_at' => $remindAt,
            'frequency' => $frequency,
            'dispatched_at' => null,
        ]);

        return $reminder->refresh();
    }
}
