<?php

namespace Tests\Feature\Telegram;

use App\Actions\Telegram\SendDueTelegramRemindersAction;
use App\Enums\MeasurementType;
use App\Enums\TelegramNotificationType;
use App\Enums\TelegramReminderFrequency;
use App\Models\TelegramConnection;
use App\Models\TelegramNotificationSubscription;
use App\Models\TelegramReminder;
use App\Models\User;
use App\Notifications\Channels\TelegramChannel;
use App\Notifications\Inventory\ProductLowStockNotification;
use App\Notifications\Telegram\TelegramReminderNotification;
use Carbon\CarbonImmutable;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class TelegramDeliveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_telegram_channel_requires_subscription(): void
    {
        $user = User::factory()->create();
        $this->connectTelegram($user, '100001');
        $notification = $this->lowStockNotification();

        $this->assertNotContains(TelegramChannel::class, $notification->via($user));

        TelegramNotificationSubscription::query()->create([
            'user_id' => $user->getKey(),
            'type' => TelegramNotificationType::LowStock,
        ]);

        $this->assertContains(TelegramChannel::class, $notification->via($user));
    }

    public function test_personal_reminder_is_sent_without_subscription(): void
    {
        $telegraph = Telegraph::fake();
        $user = User::factory()->create();
        $this->connectTelegram($user, '100002');
        $notification = new TelegramReminderNotification('Milk (2%)');

        app(TelegramChannel::class)->send($user, $notification);

        $telegraph->assertSent('*Напоминание*' . "\n\n" . 'Milk \\(2%\\)');
    }

    public function test_due_reminder_is_queued_and_marked_as_dispatched(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->connectTelegram($user, '100003');
        $now = CarbonImmutable::parse('2026-09-03 10:00:00', 'UTC');
        $reminder = TelegramReminder::query()->create([
            'user_id' => $user->getKey(),
            'message' => 'Test reminder',
            'remind_at' => $now->subMinute(),
        ]);

        $count = app(SendDueTelegramRemindersAction::class)->handle($now);

        $this->assertSame(1, $count);
        $this->assertNotNull($reminder->refresh()->dispatched_at);
        Notification::assertSentTo($user, TelegramReminderNotification::class);
    }

    public function test_due_reminder_waits_until_telegram_is_connected(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $now = CarbonImmutable::parse('2026-09-03 10:00:00', 'UTC');
        $reminder = TelegramReminder::query()->create([
            'user_id' => $user->getKey(),
            'message' => 'Test reminder',
            'remind_at' => $now->subMinute(),
        ]);

        $count = app(SendDueTelegramRemindersAction::class)->handle($now);

        $this->assertSame(0, $count);
        $this->assertNull($reminder->refresh()->dispatched_at);
        Notification::assertNothingSent();
    }

    public function test_recurring_reminder_is_rescheduled_after_dispatch(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->connectTelegram($user, '100004');
        $now = CarbonImmutable::parse('2026-09-03 10:00:00', 'UTC');
        $reminder = TelegramReminder::query()->create([
            'user_id' => $user->getKey(),
            'message' => 'Daily reminder',
            'remind_at' => $now->subMinute(),
            'frequency' => TelegramReminderFrequency::Daily,
        ]);

        $count = app(SendDueTelegramRemindersAction::class)->handle($now);

        $reminder->refresh();

        $this->assertSame(1, $count);
        $this->assertNotNull($reminder->dispatched_at);
        $this->assertTrue($reminder->remind_at->equalTo($now->addDay()));
        $this->assertSame(
            0,
            app(SendDueTelegramRemindersAction::class)->handle($now)
        );
    }

    private function connectTelegram(User $user, string $chatId): void
    {
        $bot = TelegraphBot::query()->create([
            'token' => 'test-token-' . $chatId,
            'name' => 'Test bot',
        ]);
        $chat = $bot->chats()->create([
            'chat_id' => $chatId,
            'name' => 'Test chat',
        ]);

        TelegramConnection::query()->create([
            'user_id' => $user->getKey(),
            'telegraph_chat_id' => $chat->getKey(),
            'linked_at' => now(),
        ]);
    }

    private function lowStockNotification(): ProductLowStockNotification
    {
        return new ProductLowStockNotification(
            householdUuid: 'household-uuid',
            householdName: 'Test home',
            productUuid: 'product-uuid',
            productName: 'Milk',
            measurementType: MeasurementType::Volume,
            totalQuantity: '800.000',
            threshold: '1000.000',
            becameLowAt: CarbonImmutable::parse('2026-09-03 09:00:00', 'UTC')
        );
    }
}
