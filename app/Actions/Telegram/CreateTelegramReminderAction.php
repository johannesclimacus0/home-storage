<?php

namespace App\Actions\Telegram;

use App\Enums\TelegramReminderFrequency;
use App\Models\TelegramReminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;

final class CreateTelegramReminderAction
{
    public function handle(
        User $user,
        string $message,
        CarbonImmutable $remindAt,
        ?TelegramReminderFrequency $frequency
    ): TelegramReminder {
        if (!$user->telegramConnection()->exists()) {
            throw ValidationException::withMessages([
                'telegram' => 'Сначала подключите Telegram.',
            ]);
        }

        return $user->telegramReminders()->create([
            'message' => $message,
            'remind_at' => $remindAt,
            'frequency' => $frequency,
            'dispatched_at' => null,
        ]);
    }
}
