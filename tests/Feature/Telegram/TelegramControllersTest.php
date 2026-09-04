<?php

namespace Tests\Feature\Telegram;

use App\Models\TelegramConnection;
use App\Models\User;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class TelegramControllersTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_username' => 'test_storage_bot',
            'cache.default' => 'array',
        ]);
    }

    public function test_verified_user_can_create_telegram_link(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)
            ->postJson('/api/telegram/link');

        $response->assertOk()
            ->assertJsonPath('expires_in', 600);

        $link = $response->json('link');

        $this->assertIsString($link);
        $this->assertSame('t.me', parse_url($link, PHP_URL_HOST));

        $queryString = parse_url($link, PHP_URL_QUERY);
        $this->assertIsString($queryString);
        parse_str($queryString, $query);

        $this->assertIsString($query['start'] ?? null);

        $key = 'telegram-links:' . hash('sha256', $query['start']);

        $this->assertSame($user->getKey(), Cache::get($key));
    }

    public function test_verified_user_can_view_telegram_connection_status(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $bot = TelegraphBot::query()->create([
            'token' => 'test-bot-token',
            'name' => 'Test bot',
        ]);
        $chat = $bot->chats()->create([
            'chat_id' => '100001',
            'name' => 'Test chat',
        ]);

        TelegramConnection::query()->create([
            'user_id' => $user->getKey(),
            'telegraph_chat_id' => $chat->getKey(),
            'linked_at' => now(),
        ]);

        $this->actingAs($user)
            ->getJson('/api/telegram/connection')
            ->assertOk()
            ->assertJsonPath('connected', true)
            ->assertJsonPath('chat_name', 'Test chat')
            ->assertJsonPath('timezone', 'UTC')
            ->assertJsonPath('linked_at', fn (mixed $value): bool => is_string($value));
    }

    public function test_verified_user_without_connection_sees_disconnected_status(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->getJson('/api/telegram/connection')
            ->assertOk()
            ->assertJsonPath('connected', false)
            ->assertJsonPath('linked_at', null)
            ->assertJsonPath('chat_name', null)
            ->assertJsonPath('timezone', 'UTC')
            ->assertJsonPath('timezones', fn (mixed $value): bool => is_array($value) && in_array('Europe/Moscow', $value, true));
    }

    public function test_verified_user_can_update_their_timezone(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->patchJson('/api/telegram/timezone', ['timezone' => 'Australia/Sydney'])
            ->assertOk()
            ->assertJsonPath('timezone', 'Australia/Sydney');

        $this->assertSame('Australia/Sydney', $user->refresh()->timezone);
    }

    public function test_guest_cannot_access_telegram_endpoints(): void
    {
        $this->getJson('/api/telegram/connection')->assertUnauthorized();
        $this->postJson('/api/telegram/link')->assertUnauthorized();
    }
}
