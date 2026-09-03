<?php

namespace Tests\Feature\Telegram;

use App\Actions\Telegram\CreateTelegramLinkAction;
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
}
