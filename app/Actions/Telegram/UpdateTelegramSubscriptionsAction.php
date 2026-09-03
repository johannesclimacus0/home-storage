<?php

namespace App\Actions\Telegram;

use App\Enums\TelegramNotificationType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateTelegramSubscriptionsAction
{
    /**
     * @param  array<int, TelegramNotificationType>  $types
     */
    public function handle(User $user, array $types): void
    {
        DB::transaction(function () use ($user, $types): void {
            $values = collect($types)
                ->map(fn (TelegramNotificationType $type): string => $type->value)
                ->unique()
                ->values();

            $user->telegramNotificationSubscriptions()
                ->whereNotIn('type', $values)
                ->delete();

            foreach ($values as $value) {
                $user->telegramNotificationSubscriptions()->firstOrCreate([
                    'type' => $value,
                ]);
            }
        });
    }
}
