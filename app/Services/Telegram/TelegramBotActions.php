<?php

namespace App\Services\Telegram;

use App\Actions\Shopping\AddShoppingListItemAction;
use App\Actions\Shopping\CompleteShoppingListItemAction;
use App\Actions\Shopping\DeleteShoppingListItemAction;
use App\Actions\Shopping\ListShoppingListItemsAction;
use App\Actions\Shopping\ReopenShoppingListItemAction;
use App\Actions\Shopping\UpdateShoppingListItemAction;
use App\Actions\Telegram\CreateTelegramReminderAction;
use App\Actions\Telegram\DeleteTelegramReminderAction;
use App\Actions\Telegram\ListTelegramRemindersAction;
use App\Actions\Telegram\UpdateTelegramReminderAction;
use App\Actions\Telegram\UpdateTelegramSubscriptionsAction;
use App\DTO\Shopping\AddShoppingListItemData;
use App\DTO\Shopping\UpdateShoppingListItemData;
use App\Enums\MeasurementType;
use App\Enums\MeasurementUnit;
use App\Enums\TelegramNotificationType;
use App\Enums\TelegramReminderFrequency;
use App\Models\Household;
use App\Models\HouseholdProduct;
use App\Models\ShoppingListItem;
use App\Models\TelegramConnection;
use App\Models\TelegramReminder;
use App\Models\User;
use Carbon\CarbonImmutable;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final readonly class TelegramBotActions
{
    public function __construct(
        private ListTelegramRemindersAction $listReminders,
        private CreateTelegramReminderAction $createReminder,
        private UpdateTelegramReminderAction $updateReminder,
        private DeleteTelegramReminderAction $deleteReminder,
        private UpdateTelegramSubscriptionsAction $updateSubscriptions,
        private ListShoppingListItemsAction $listShoppingItems,
        private AddShoppingListItemAction $addShoppingItem,
        private UpdateShoppingListItemAction $updateShoppingItem,
        private CompleteShoppingListItemAction $completeShoppingItem,
        private ReopenShoppingListItemAction $reopenShoppingItem,
        private DeleteShoppingListItemAction $deleteShoppingItem
    ) {}

    public function userForChat(TelegraphChat $chat): ?User
    {
        return TelegramConnection::query()
            ->where('telegraph_chat_id', $chat->getKey())
            ->with('user')
            ->first()
            ?->user;
    }

    /** @return Collection<int, Household> */
    public function households(User $user): Collection
    {
        return $user->householdMemberships()
            ->with('household')
            ->get()
            ->pluck('household')
            ->filter()
            ->values();
    }

    /** @return Collection<int, TelegramReminder> */
    public function reminders(User $user): Collection
    {
        return $this->listReminders->handle($user);
    }

    public function createReminder(User $user, string $input): TelegramReminder
    {
        [$message, $remindAt, $frequency] = $this->parseReminderInput($input, $user->timezone);

        return $this->createReminder->handle($user, $message, $remindAt->utc(), $frequency);
    }

    public function updateReminder(User $user, int $reminderId, string $input): TelegramReminder
    {
        $reminder = $user->telegramReminders()->findOrFail($reminderId);
        [$message, $remindAt, $frequency] = $this->parseReminderInput($input, $user->timezone);

        return $this->updateReminder->handle($user, $reminder->uuid, $message, $remindAt->utc(), $frequency);
    }

    public function deleteReminder(User $user, int $reminderId): void
    {
        $reminder = $user->telegramReminders()->findOrFail($reminderId);
        $this->deleteReminder->handle($user, $reminder->uuid);
    }

    public function lowStockSubscriptionEnabled(User $user): bool
    {
        return $user->telegramNotificationSubscriptions()
            ->where('type', TelegramNotificationType::LowStock->value)
            ->exists();
    }

    public function toggleLowStockSubscription(User $user): bool
    {
        $enabled = !$this->lowStockSubscriptionEnabled($user);

        $this->updateSubscriptions->handle(
            $user,
            $enabled ? [TelegramNotificationType::LowStock] : []
        );

        return $enabled;
    }

    public function household(User $user, int $householdId): Household
    {
        return Household::query()
            ->whereKey($householdId)
            ->whereHas('householdMemberships', fn ($query) => $query->where('user_id', $user->getKey()))
            ->firstOrFail();
    }

    /** @return Collection<int, ShoppingListItem> */
    public function shoppingItems(User $user, int $householdId): Collection
    {
        $household = $this->household($user, $householdId);

        return $this->listShoppingItems->handle($household->uuid, $user->getKey());
    }

    /** @return Collection<int, HouseholdProduct> */
    public function availableProducts(User $user, int $householdId): Collection
    {
        $household = $this->household($user, $householdId);

        return $household->householdProducts()
            ->with('product')
            ->get()
            ->sortBy(fn (HouseholdProduct $product): string => $product->product->name)
            ->values();
    }

    public function addShoppingItem(User $user, int $householdId, int $productId, string $input): ShoppingListItem
    {
        $household = $this->household($user, $householdId);
        $householdProduct = $household->householdProducts()
            ->with('product')
            ->where('product_id', $productId)
            ->firstOrFail();

        return $this->addShoppingItem->handle(new AddShoppingListItemData(
            householdUuid: $household->uuid,
            actorUserId: $user->getKey(),
            productUuid: $householdProduct->product->uuid,
            quantity: $this->quantity($input),
            unit: $this->baseUnit($householdProduct->product->measurement_type)
        ));
    }

    public function updateShoppingItem(User $user, int $itemId, string $input): ShoppingListItem
    {
        $item = $this->shoppingItem($user, $itemId);

        return $this->updateShoppingItem->handle(new UpdateShoppingListItemData(
            householdUuid: $item->household->uuid,
            actorUserId: $user->getKey(),
            itemUuid: $item->uuid,
            quantity: $this->quantity($input),
            unit: $this->baseUnit($item->product->measurement_type)
        ));
    }

    public function toggleShoppingItem(User $user, int $itemId): ShoppingListItem
    {
        $item = $this->shoppingItem($user, $itemId);

        if ($item->completed_at === null) {
            return $this->completeShoppingItem->handle(
                $item->household->uuid,
                $user->getKey(),
                $item->uuid
            );
        }

        return $this->reopenShoppingItem->handle(
            $item->household->uuid,
            $user->getKey(),
            $item->uuid
        );
    }

    public function deleteShoppingItem(User $user, int $itemId): int
    {
        $item = $this->shoppingItem($user, $itemId);
        $householdId = $item->household_id;

        $this->deleteShoppingItem->handle(
            $item->household->uuid,
            $user->getKey(),
            $item->uuid
        );

        return $householdId;
    }

    private function shoppingItem(User $user, int $itemId): ShoppingListItem
    {
        return ShoppingListItem::query()
            ->with(['household', 'product'])
            ->whereKey($itemId)
            ->whereHas(
                'household.householdMemberships',
                fn ($query) => $query->where('user_id', $user->getKey())
            )
            ->firstOrFail();
    }

    /** @return array{string, CarbonImmutable, TelegramReminderFrequency|null} */
    private function parseReminderInput(string $input, string $timezone): array
    {
        $parts = array_map('trim', explode('|', $input));

        if (count($parts) < 2 || $parts[0] === '' || $parts[1] === '') {
            throw new InvalidArgumentException('Используйте формат: текст | 04.09.2026 18:00 | daily');
        }

        try {
            $remindAt = $this->parseReminderDate($parts[1], $timezone);
        } catch (\Throwable) {
            throw new InvalidArgumentException('Укажите дату в формате ДД.ММ.ГГГГ ЧЧ:ММ.');
        }

        if ($remindAt->isPast()) {
            throw new InvalidArgumentException('Дата напоминания должна быть в будущем.');
        }

        $frequency = match (strtolower($parts[2] ?? 'once')) {
            '', 'once', 'один раз' => null,
            'hourly', 'каждый час' => TelegramReminderFrequency::Hourly,
            'daily', 'каждый день' => TelegramReminderFrequency::Daily,
            'weekly', 'каждую неделю' => TelegramReminderFrequency::Weekly,
            'monthly', 'каждый месяц' => TelegramReminderFrequency::Monthly,
            default => throw new InvalidArgumentException('Повтор: once, hourly, daily, weekly или monthly.'),
        };

        return [$parts[0], $remindAt, $frequency];
    }

    private function parseReminderDate(string $value, string $timezone): CarbonImmutable
    {
        $format = 'd.m.Y H:i';
        $date = CarbonImmutable::createFromFormat($format, $value, $timezone);

        if ($date === false || $date->format($format) !== $value) {
            throw new InvalidArgumentException('Invalid reminder date.');
        }

        return $date;
    }

    private function quantity(string $input): string
    {
        $quantity = str_replace(',', '.', trim($input));

        if ((float) $quantity <= 0) {
            throw new InvalidArgumentException('Введите количество больше нуля.');
        }

        return $quantity;
    }

    private function baseUnit(MeasurementType $type): MeasurementUnit
    {
        return match ($type) {
            MeasurementType::Mass => MeasurementUnit::Gram,
            MeasurementType::Volume => MeasurementUnit::Milliliter,
            MeasurementType::Count => MeasurementUnit::Piece,
        };
    }
}
