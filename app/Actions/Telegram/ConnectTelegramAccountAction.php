<?php

namespace App\Actions\Telegram;

use App\Exceptions\Telegram\TelegramChatAlreadyConnectedException;
use App\Models\TelegramConnection;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Facades\DB;

final readonly class ConnectTelegramAccountAction
{
    public function __construct(
        private ConsumeTelegramLinkAction $consumeLink
    ) {}

    public function handle(string $token, TelegraphChat $chat): TelegramConnection
    {
        $user = $this->consumeLink->handle($token);

        return DB::transaction(function () use ($chat, $user): TelegramConnection {
            if (! $chat->exists) {
                $chat->save();
            }

            $chatBelongsToAnotherUser = TelegramConnection::query()
                ->where('telegraph_chat_id', $chat->getKey())
                ->where('user_id', '!=', $user->getKey())
                ->exists();

            if ($chatBelongsToAnotherUser) {
                throw new TelegramChatAlreadyConnectedException('Chat already connected');
            }

            return TelegramConnection::query()->updateOrCreate(
                ['user_id' => $user->getKey()],
                [
                    'telegraph_chat_id' => $chat->getKey(),
                    'linked_at' => now(),
                ]
            );
        });
    }
}
