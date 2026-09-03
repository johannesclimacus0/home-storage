<?php

namespace App\Actions\Telegram;

use App\Models\TelegramReminder;
use App\Notifications\Telegram\TelegramReminderNotification;
use Carbon\CarbonImmutable;

final class SendDueTelegramRemindersAction
{
    public function handle(CarbonImmutable $now): int
    {
        $dispatchedCount = 0;

        TelegramReminder::query()
            ->where('remind_at', '<=', $now)
            ->where(function ($query): void {
                $query->whereNull('dispatched_at')
                    ->orWhereNotNull('frequency');
            })
            ->with('user.telegramConnection.chat')
            ->orderBy('id')
            ->chunkById(100, function ($reminders) use ($now, &$dispatchedCount): void {
                foreach ($reminders as $reminder) {
                    if ($reminder->user->telegramConnection?->chat === null) {
                        continue;
                    }

                    $reminder->user->notify(
                        new TelegramReminderNotification($reminder->message)
                    );

                    $attributes = ['dispatched_at' => $now];

                    if ($reminder->frequency !== null) {
                        $attributes['remind_at'] = $reminder->frequency->nextAfter($now);
                    }

                    $reminder->update($attributes);
                    $dispatchedCount++;
                }
            });

        return $dispatchedCount;
    }
}
