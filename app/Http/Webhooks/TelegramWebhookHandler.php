<?php
declare(strict_types=1);

namespace App\Http\Webhooks;

use App\Actions\Telegram\ConnectTelegramAccountAction;
use App\Exceptions\Telegram\InvalidTelegramLinkException;
use App\Exceptions\Telegram\TelegramChatAlreadyConnectedException;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;

final class TelegramWebhookHandler extends WebhookHandler
{
    public function __construct(
        private readonly ConnectTelegramAccountAction $connectAccount
    ) {
        parent::__construct();
    }

    public function start(string $token = ''): void
    {
        $token = $this->startToken($token);

        if ($token === '') {
            $this->sendMessage('Откройте ссылку подключения в профиле Home Storage');

            return;
        }

        try {
            $this->connectAccount->handle($token, $this->chat);
        } catch (InvalidTelegramLinkException|ModelNotFoundException) {
            $this->sendMessage('Ссылка подключения недействительна или устарела');

            return;
        } catch (TelegramChatAlreadyConnectedException) {
            $this->sendMessage('Этот Telegram уже подключён к другому аккаунту');

            return;
        }

        $this->sendMessage('Telegram успешно подключён к Home Storage');
    }

    private function sendMessage(string $message): void
    {
        $this->chat->html($message)->send();
    }

    private function startToken(string $token): string
    {
        $token = trim($token);

        if ($token !== '') {
            return $token;
        }

        $text = $this->request->input('message.text');

        if (!is_string($text)) {
            return '';
        }

        if (!preg_match('/^\/start(?:@\w+)?\s+([A-Za-z0-9_-]{1,64})$/', trim($text), $matches)) {
            return '';
        }

        return $matches[1];
    }
}
