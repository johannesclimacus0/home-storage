<?php

namespace App\Http\Webhooks;

use App\Actions\Telegram\ConnectTelegramAccountAction;
use App\Exceptions\Telegram\InvalidTelegramLinkException;
use App\Exceptions\Telegram\TelegramChatAlreadyConnectedException;
use App\Models\ShoppingListItem;
use App\Models\TelegramReminder;
use App\Models\User;
use App\Services\Telegram\TelegramBotActions;
use App\Services\Telegram\TelegramConversationState;
use App\Support\Telegram\TelegramMarkdown;
use DefStudio\Telegraph\Client\TelegraphResponse;
use DefStudio\Telegraph\Handlers\WebhookHandler;
use DefStudio\Telegraph\Keyboard\Button;
use DefStudio\Telegraph\Keyboard\Keyboard;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Stringable;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

final class TelegramWebhookHandler extends WebhookHandler
{
    public function start(string $token = ''): void
    {
        $token = $this->startToken($token);

        if ($token === '') {
            if ($this->connectedUser() !== null) {
                $this->menu();

                return;
            }

            $this->sendMessage('Откройте ссылку подключения в профиле Home Storage');

            return;
        }

        try {
            $this->connectAccount()->handle($token, $this->chat);
        } catch (InvalidTelegramLinkException|ModelNotFoundException) {
            $this->sendMessage('Ссылка подключения недействительна или устарела');

            return;
        } catch (TelegramChatAlreadyConnectedException) {
            $this->sendMessage('Этот Telegram уже подключён к другому аккаунту');

            return;
        }

        $this->sendMessage('Telegram успешно подключён к Home Storage');
        $this->menu();
    }

    public function menu(): void
    {
        if ($this->requireUser() === null) {
            return;
        }

        $this->sendKeyboard('Что хотите сделать?', Keyboard::make()
            ->row([
                Button::make('Напоминания')->action('showReminders'),
                Button::make('Покупки')->action('showShopping'),
            ])
            ->row([
                Button::make('Подписки')->action('showSubscriptions'),
            ]));
    }

    public function help(): void
    {
        if ($this->requireUser() === null) {
            return;
        }

        $this->sendKeyboard(
            '<b>Home Storage</b>' . "\n\n"
            . '/menu — главное меню' . "\n\n"
            . '/reminders — личные напоминания' . "\n\n"
            . '/shopping — списки покупок' . "\n\n"
            . '/notifications — подписки на уведомления' . "\n\n"
            . '/cancel — отменить текущий ввод' . "\n\n"
            . '/help — показать эту подсказку',
            Keyboard::make()->row([$this->menuButton()])
        );
    }

    public function reminders(): void
    {
        $this->showReminders();
    }

    public function shopping(): void
    {
        $this->showShopping();
    }

    public function notifications(): void
    {
        $this->showSubscriptions();
    }

    public function cancel(): void
    {
        $this->conversation()->forget($this->conversationChatId());
        $this->sendMessage('Действие отменено.');
        $this->menu();
    }

    public function showSubscriptions(): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        $enabled = $this->actions()->lowStockSubscriptionEnabled($user);

        $this->sendKeyboard(
            '<b>Уведомления</b>' . "\n\n" . 'Заканчивающиеся продукты: ' . ($enabled ? 'включены' : 'выключены'),
            Keyboard::make()
                ->row([
                    Button::make($enabled ? 'Отключить' : 'Подключить')->action('toggleLowStock'),
                ])
                ->row([$this->menuButton()])
        );
    }

    public function toggleLowStock(): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        $this->actions()->toggleLowStockSubscription($user);
        $this->showSubscriptions();
    }

    public function showReminders(): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        $reminders = $this->actions()->reminders($user)->take(10);
        $lines = ['<b>Напоминания</b>'];
        $keyboard = Keyboard::make();

        foreach ($reminders as $index => $reminder) {
            $number = $index + 1;
            $status = $reminder->dispatched_at !== null && $reminder->frequency === null ? ' (отправлено)' : '';
            $lines[] = $number . '. ' . $this->escape($reminder->message)
                . ' — ' . $this->escape(
                    $reminder->remind_at->setTimezone($user->timezone)->format('d.m.Y H:i')
                ) . $status;
            $keyboard = $keyboard->row([
                Button::make('Изменить ' . $number)->action('promptEditReminder')->param('reminder', $reminder->getKey()),
                Button::make('Удалить ' . $number)->action('confirmDeleteReminder')->param('reminder', $reminder->getKey()),
            ]);
        }

        if ($reminders->isEmpty()) {
            $lines[] = 'Пока пусто.';
        }

        $keyboard = $keyboard
            ->row([Button::make('Новое напоминание')->action('promptCreateReminder')])
            ->row([$this->menuButton()]);

        $this->sendKeyboard(implode("\n\n", $lines), $keyboard);
    }

    public function promptCreateReminder(): void
    {
        if ($this->requireUser() === null) {
            return;
        }

        $this->conversation()->put($this->conversationChatId(), ['action' => 'create_reminder']);
        $this->sendMessage(
            'Отправьте напоминание одной строкой:' . "\n\n"
            . '<code>Купить молоко | 04.09.2026 18:00 | daily</code>' . "\n\n"
            . 'Повтор: once, hourly, daily, weekly или monthly. Для отмены: /cancel'
        );
    }

    public function promptEditReminder(int $reminder): void
    {
        $user = $this->requireUser();

        if ($user === null || !$user->telegramReminders()->whereKey($reminder)->exists()) {
            return;
        }

        $this->conversation()->put($this->conversationChatId(), [
            'action' => 'edit_reminder',
            'reminder' => $reminder,
        ]);
        $this->sendMessage(
            'Отправьте новые данные:' . "\n\n"
            . '<code>Текст | 04.09.2026 18:00 | once</code>' . "\n\n"
            . 'Для отмены: /cancel'
        );
    }

    public function confirmDeleteReminder(int $reminder): void
    {
        $user = $this->requireUser();
        $model = $user?->telegramReminders()->find($reminder);

        if (!$model instanceof TelegramReminder) {
            return;
        }

        $this->sendKeyboard(
            'Удалить «' . $this->escape($model->message) . '»?',
            Keyboard::make()->row([
                Button::make('Удалить')->action('deleteReminder')->param('reminder', $reminder),
                Button::make('Оставить')->action('showReminders'),
            ])
        );
    }

    public function deleteReminder(int $reminder): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        $this->actions()->deleteReminder($user, $reminder);
        $this->sendMessage('Напоминание удалено.');
        $this->showReminders();
    }

    public function showShopping(): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        $households = $this->actions()->households($user);
        $keyboard = Keyboard::make();

        foreach ($households as $household) {
            $keyboard = $keyboard->row([
                Button::make($household->name)
                    ->action('showShoppingList')
                    ->param('household', $household->getKey()),
            ]);
        }

        if ($households->isEmpty()) {
            $this->sendKeyboard('У вас пока нет дома.', Keyboard::make()->row([$this->menuButton()]));

            return;
        }

        $this->sendKeyboard(
            '<b>Список покупок</b>' . "\n\n" . 'Выберите дом:',
            $keyboard->row([$this->menuButton()])
        );
    }

    public function showShoppingList(int $household): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        $home = $this->actions()->household($user, $household);
        $items = $this->actions()->shoppingItems($user, $household)->take(10);
        $lines = ['<b>' . $this->escape($home->name) . ': покупки</b>'];
        $keyboard = Keyboard::make();
        foreach ($items as $index => $item) {
            $number = $index + 1;
            $status = $item->completed_at === null ? '' : ' (куплено)';
            $lines[] = $number . '. ' . $this->escape($item->product->name)
                . ' — ' . $this->escape((string) $item->quantity) . $status;
            $keyboard = $keyboard->row([
                Button::make(($item->completed_at === null ? 'Куплено ' : 'Вернуть ') . $number)
                    ->action('toggleShoppingItem')->param('item', $item->getKey()),
                Button::make('Изменить ' . $number)
                    ->action('promptEditShoppingItem')->param('item', $item->getKey()),
                Button::make('Удалить ' . $number)
                    ->action('confirmDeleteShoppingItem')->param('item', $item->getKey()),
            ]);
        }

        if ($items->isEmpty()) {
            $lines[] = 'Пока пусто.';
        }

        $keyboard = $keyboard
            ->row([
                Button::make('Добавить товар')->action('chooseShoppingProduct')->param('household', $household),
            ])
            ->row([
                Button::make('Сменить дом')->action('showShopping'),
                $this->menuButton(),
            ]);

        $this->sendKeyboard(implode("\n\n", $lines), $keyboard);
    }

    public function chooseShoppingProduct(int $household): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        $products = $this->actions()->availableProducts($user, $household)->take(20);
        $keyboard = Keyboard::make();

        foreach ($products as $householdProduct) {
            $keyboard = $keyboard->row([
                Button::make($householdProduct->product->name)
                    ->action('promptAddShoppingItem')
                    ->param('household', $household)
                    ->param('product', $householdProduct->product_id),
            ]);
        }

        if ($products->isEmpty()) {
            $this->sendKeyboard(
                'Сначала добавьте товары дому в веб-приложении.',
                Keyboard::make()->row([
                    Button::make('Назад')->action('showShoppingList')->param('household', $household),
                ])
            );

            return;
        }

        $this->sendKeyboard(
            'Какой товар добавить?',
            $keyboard->row([
                Button::make('Назад')->action('showShoppingList')->param('household', $household),
            ])
        );
    }

    public function promptAddShoppingItem(int $household, int $product): void
    {
        if ($this->requireUser() === null) {
            return;
        }

        $this->conversation()->put($this->conversationChatId(), [
            'action' => 'add_shopping_item',
            'household' => $household,
            'product' => $product,
        ]);
        $this->sendMessage('Введите количество. Для отмены: /cancel');
    }

    public function promptEditShoppingItem(int $item): void
    {
        if ($this->requireUser() === null) {
            return;
        }

        $this->conversation()->put($this->conversationChatId(), [
            'action' => 'edit_shopping_item',
            'item' => $item,
        ]);
        $this->sendMessage('Введите новое количество. Для отмены: /cancel');
    }

    public function toggleShoppingItem(int $item): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        $updated = $this->actions()->toggleShoppingItem($user, $item);
        $this->showShoppingList($updated->household_id);
    }

    public function confirmDeleteShoppingItem(int $item): void
    {
        $user = $this->requireUser();
        $model = $user === null ? null : ShoppingListItem::query()
            ->with('product')
            ->whereKey($item)
            ->whereHas('household.householdMemberships', fn ($query) => $query->where('user_id', $user->getKey()))
            ->first();

        if (!$model instanceof ShoppingListItem) {
            return;
        }

        $this->sendKeyboard(
            'Удалить «' . $this->escape($model->product->name) . '»?',
            Keyboard::make()->row([
                Button::make('Удалить')->action('deleteShoppingItem')->param('item', $item),
                Button::make('Оставить')->action('showShoppingList')->param('household', $model->household_id),
            ])
        );
    }

    public function deleteShoppingItem(int $item): void
    {
        $user = $this->requireUser();

        if ($user === null) {
            return;
        }

        $householdId = $this->actions()->deleteShoppingItem($user, $item);
        $this->sendMessage('Товар удалён из списка.');
        $this->showShoppingList($householdId);
    }

    protected function handleChatMessage(Stringable $text): void
    {
        $user = $this->requireUser();
        $state = $this->conversation()->get($this->conversationChatId());

        if ($user === null || $state === null) {
            $this->menu();

            return;
        }

        try {
            match ($state['action'] ?? null) {
                'create_reminder' => $this->actions()->createReminder($user, (string) $text),
                'edit_reminder' => $this->actions()->updateReminder($user, (int) $state['reminder'], (string) $text),
                'add_shopping_item' => $this->actions()->addShoppingItem(
                    $user,
                    (int) $state['household'],
                    (int) $state['product'],
                    (string) $text
                ),
                'edit_shopping_item' => $this->actions()->updateShoppingItem(
                    $user,
                    (int) $state['item'],
                    (string) $text
                ),
                default => throw new InvalidArgumentException('Действие устарело. Начните заново.'),
            };
        } catch (InvalidArgumentException $exception) {
            $this->sendMessage($this->escape($exception->getMessage()));

            return;
        }

        $this->conversation()->forget($this->conversationChatId());
        $this->sendMessage('Сохранено.');

        if (in_array($state['action'], ['create_reminder', 'edit_reminder'], true)) {
            $this->showReminders();

            return;
        }

        if ($state['action'] === 'add_shopping_item') {
            $this->showShoppingList((int) $state['household']);

            return;
        }

        $item = ShoppingListItem::query()->find((int) $state['item']);

        if ($item !== null) {
            $this->showShoppingList($item->household_id);
        }
    }

    protected function handleUnknownCommand(Stringable $text): void
    {
        $this->sendMessage(
            'Неизвестная команда «' . $this->escape((string) $text) . '». Используйте /help.'
        );
    }

    protected function onFailure(Throwable $throwable): void
    {
        if ($throwable instanceof NotFoundHttpException) {
            throw $throwable;
        }

        report($throwable);

        rescue(
            fn () => $this->sendMessage('Не удалось выполнить действие. Попробуйте ещё раз или используйте /help.'),
            report: false
        );
    }

    private function requireUser(): ?User
    {
        $user = $this->connectedUser();

        if ($user === null) {
            $this->sendMessage('Сначала подключите Telegram в профиле Home Storage.');
        }

        return $user;
    }

    private function connectedUser(): ?User
    {
        return $this->chat->exists ? $this->actions()->userForChat($this->chat) : null;
    }

    private function connectAccount(): ConnectTelegramAccountAction
    {
        return app(ConnectTelegramAccountAction::class);
    }

    private function actions(): TelegramBotActions
    {
        return app(TelegramBotActions::class);
    }

    private function conversation(): TelegramConversationState
    {
        return app(TelegramConversationState::class);
    }

    private function menuButton(): Button
    {
        return Button::make('Меню')->action('menu');
    }

    private function sendMessage(string $message): void
    {
        $this->ensureTelegramAccepted(
            $this->chat->markdownV2($this->markdownText($message))->send()
        );
    }

    private function sendKeyboard(string $message, Keyboard $keyboard): void
    {
        $this->ensureTelegramAccepted(
            $this->chat->markdownV2($this->markdownText($message))->keyboard($keyboard)->send()
        );
    }

    private function markdownText(string $message): string
    {
        $formatted = [];
        $message = preg_replace_callback(
            '/<(b|code)>(.*?)<\/\1>/s',
            function (array $matches) use (&$formatted): string {
                $placeholder = 'TELEGRAMFORMAT' . count($formatted);
                $content = html_entity_decode(
                    strip_tags($matches[2]),
                    ENT_QUOTES | ENT_SUBSTITUTE,
                    'UTF-8'
                );

                $formatted[$placeholder] = $matches[1] === 'b'
                    ? '*' . TelegramMarkdown::escape($content) . '*'
                    : '`' . str_replace(['\\', '`'], ['\\\\', '\\`'], $content) . '`';

                return $placeholder;
            },
            $message
        ) ?? $message;

        $plainText = html_entity_decode(
            strip_tags($message),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8'
        );

        return strtr(TelegramMarkdown::escape($plainText), $formatted);
    }

    private function ensureTelegramAccepted(TelegraphResponse $response): void
    {
        if ($response->telegraphOk()) {
            return;
        }

        $description = $response->json('description');

        throw new RuntimeException(
            is_string($description) ? 'Telegram API: ' . $description : 'Telegram API rejected the request.'
        );
    }

    private function conversationChatId(): string
    {
        return (string) $this->chat->chat_id;
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
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
