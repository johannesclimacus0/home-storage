<?php

namespace Tests\Feature\Telegram;

use App\Actions\Telegram\CreateTelegramLinkAction;
use App\Enums\MeasurementType;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\TelegramConnection;
use App\Models\User;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

class TelegramWebhookHandlerTest extends TestCase
{
    use RefreshDatabase;

    private TelegraphBot $bot;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_username' => 'test_storage_bot',
            'cache.default' => 'array',
            'telegraph.webhook.secret' => 'test-webhook-secret',
            'telegraph.security.allow_messages_from_unknown_chats' => true,
            'telegraph.security.store_unknown_chats_in_db' => false,
        ]);

        $this->bot = TelegraphBot::query()->create([
            'token' => 'test-bot-token',
            'name' => 'Test bot',
        ]);
    }

    public function test_start_command_connects_user_to_telegram_chat(): void
    {
        $telegraph = Telegraph::fake();
        $user = User::factory()->create();
        $token = $this->createTokenFor($user);

        $response = $this->postWebhook('/start ' . $token, 100001);

        $response->assertSuccessful();
        $telegraph->assertSent('Telegram успешно подключён к Home Storage');
        $this->assertDatabaseHas('telegram_connections', [
            'user_id' => $user->getKey(),
        ]);
        $this->assertDatabaseHas('telegraph_chats', [
            'chat_id' => '100001',
            'telegraph_bot_id' => $this->bot->getKey(),
        ]);
    }

    public function test_start_command_rejects_invalid_link(): void
    {
        $telegraph = Telegraph::fake();

        $response = $this->postWebhook('/start ' . str_repeat('a', 48), 100002);

        $response->assertSuccessful();
        $telegraph->assertSent('Ссылка подключения недействительна или устарела');
        $this->assertDatabaseMissing('telegraph_chats', [
            'chat_id' => '100002',
        ]);
        $this->assertDatabaseCount('telegram_connections', 0);
    }

    public function test_connected_user_can_open_bot_menu(): void
    {
        $telegraph = Telegraph::fake();
        $user = User::factory()->create();
        $this->connectUser($user, 100003);

        $this->postWebhook('/menu', 100003)->assertSuccessful();

        $telegraph->assertSent('Что хотите сделать?');
    }

    public function test_connected_user_can_open_every_main_bot_section(): void
    {
        $telegraph = Telegraph::fake();
        $user = User::factory()->create();
        $this->connectUser($user, 100006);
        $household = Household::factory()->create();
        HouseholdMembership::factory()->for($household)->for($user)->create();

        $this->postCallback('action:showReminders', 100006)->assertSuccessful();
        $this->postCallback('action:showShopping', 100006)->assertSuccessful();
        $this->postCallback('action:showSubscriptions', 100006)->assertSuccessful();
        $this->postWebhook('/help', 100006)->assertSuccessful();

        $telegraph->assertSent("*Напоминания*\n\nПока пусто\\.", exact: false);
        $telegraph->assertSent("*Список покупок*\n\nВыберите дом:", exact: false);
        $telegraph->assertSent("*Уведомления*\n\nЗаканчивающиеся продукты: выключены");
        $telegraph->assertSent('*Home Storage*', exact: false);
    }

    public function test_user_can_manage_reminders_from_bot(): void
    {
        Telegraph::fake();
        $user = User::factory()->create(['timezone' => 'Australia/Sydney']);
        $this->connectUser($user, 100004);
        $firstLocalDate = now('Australia/Sydney')->addDay()->startOfMinute();
        $firstDate = $firstLocalDate->format('d.m.Y H:i');
        $secondDate = now('Australia/Sydney')->addDays(2)->format('d.m.Y H:i');

        $this->postCallback('action:promptCreateReminder', 100004)->assertSuccessful();
        $this->postWebhook("Buy milk | {$firstDate} | daily", 100004)->assertSuccessful();

        $reminder = $user->telegramReminders()->firstOrFail();
        $this->assertSame('Buy milk', $reminder->message);
        $this->assertSame('daily', $reminder->frequency->value);
        $this->assertTrue($reminder->remind_at->equalTo($firstLocalDate->utc()));

        $this->postCallback(
            'action:promptEditReminder;reminder:' . $reminder->getKey(),
            100004
        )->assertSuccessful();
        $this->postWebhook("Buy bread | {$secondDate} | once", 100004)->assertSuccessful();

        $this->assertSame('Buy bread', $reminder->refresh()->message);
        $this->assertNull($reminder->frequency);

        $this->postCallback(
            'action:deleteReminder;reminder:' . $reminder->getKey(),
            100004
        )->assertSuccessful();

        $this->assertDatabaseMissing('telegram_reminders', ['id' => $reminder->getKey()]);
    }

    public function test_user_can_manage_shopping_list_from_bot(): void
    {
        Telegraph::fake();
        $user = User::factory()->create();
        $this->connectUser($user, 100005);
        $household = Household::factory()->create();
        HouseholdMembership::factory()->for($household)->for($user)->create();
        $product = Product::factory()->create([
            'name' => 'Milk',
            'measurement_type' => MeasurementType::Count,
        ]);
        HouseholdProduct::factory()->for($household)->for($product)->create();

        $this->postCallback(
            "action:promptAddShoppingItem;household:{$household->getKey()};product:{$product->getKey()}",
            100005
        )->assertSuccessful();
        $this->postWebhook('2', 100005)->assertSuccessful();

        $item = $household->shoppingListItems()->firstOrFail();
        $this->assertSame('2.000', $item->quantity);

        $this->postCallback(
            'action:promptEditShoppingItem;item:' . $item->getKey(),
            100005
        )->assertSuccessful();
        $this->postWebhook('3', 100005)->assertSuccessful();
        $this->assertSame('3.000', $item->refresh()->quantity);

        $this->postCallback(
            'action:toggleShoppingItem;item:' . $item->getKey(),
            100005
        )->assertSuccessful();
        $this->assertNotNull($item->refresh()->completed_at);

        $this->postCallback(
            'action:deleteShoppingItem;item:' . $item->getKey(),
            100005
        )->assertSuccessful();
        $this->assertDatabaseMissing('shopping_list_items', ['id' => $item->getKey()]);
    }

    private function postWebhook(string $text, int $chatId): TestResponse
    {
        return $this
            ->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-webhook-secret')
            ->postJson('/telegraph/' . $this->bot->token . '/webhook', [
                'update_id' => 1,
                'message' => [
                    'message_id' => 1,
                    'date' => now()->timestamp,
                    'text' => $text,
                    'chat' => [
                        'id' => $chatId,
                        'type' => 'private',
                        'first_name' => 'Test user',
                    ],
                    'from' => [
                        'id' => $chatId,
                        'is_bot' => false,
                        'first_name' => 'Test user',
                    ],
                ],
            ]);
    }

    private function createTokenFor(User $user): string
    {
        $link = app(CreateTelegramLinkAction::class)->handle($user);
        $queryString = parse_url($link, PHP_URL_QUERY);

        $this->assertIsString($queryString);

        parse_str($queryString, $query);

        $this->assertArrayHasKey('start', $query);
        $this->assertIsString($query['start']);

        return $query['start'];
    }

    private function connectUser(User $user, int $chatId): void
    {
        $chat = $this->bot->chats()->create([
            'chat_id' => (string) $chatId,
            'name' => 'Test chat',
        ]);

        TelegramConnection::query()->create([
            'user_id' => $user->getKey(),
            'telegraph_chat_id' => $chat->getKey(),
            'linked_at' => now(),
        ]);
    }

    private function postCallback(string $data, int $chatId): TestResponse
    {
        return $this
            ->withHeader('X-Telegram-Bot-Api-Secret-Token', 'test-webhook-secret')
            ->postJson('/telegraph/' . $this->bot->token . '/webhook', [
                'update_id' => 2,
                'callback_query' => [
                    'id' => $chatId,
                    'data' => $data,
                    'from' => [
                        'id' => $chatId,
                        'is_bot' => false,
                        'first_name' => 'Test user',
                    ],
                    'message' => [
                        'message_id' => 2,
                        'date' => now()->timestamp,
                        'text' => 'Menu',
                        'chat' => [
                            'id' => $chatId,
                            'type' => 'private',
                            'first_name' => 'Test user',
                        ],
                    ],
                ],
            ]);
    }
}
