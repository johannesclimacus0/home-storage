<?php

namespace Tests\Feature\Telegram;

use App\Actions\Telegram\ConnectTelegramAccountAction;
use App\Actions\Telegram\CreateTelegramLinkAction;
use App\Exceptions\Telegram\InvalidTelegramLinkException;
use App\Exceptions\Telegram\TelegramChatAlreadyConnectedException;
use App\Models\TelegramConnection;
use App\Models\User;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConnectTelegramAccountActionTest extends TestCase
{
    use RefreshDatabase;

    private CreateTelegramLinkAction $createLink;

    private ConnectTelegramAccountAction $connectAccount;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_username' => 'test_storage_bot',
            'cache.default' => 'array',
        ]);

        $this->createLink = app(CreateTelegramLinkAction::class);
        $this->connectAccount = app(ConnectTelegramAccountAction::class);
    }

    public function test_it_connects_user_to_telegram_chat_with_valid_link(): void
    {
        $user = User::factory()->create();
        $chat = $this->makeUnsavedChat('100001');
        $token = $this->createTokenFor($user);

        $connection = $this->connectAccount->handle($token, $chat);

        $this->assertTrue($chat->exists);
        $this->assertTrue($connection->user->is($user));
        $this->assertTrue($connection->chat->is($chat));
        $this->assertNotNull($connection->linked_at);
        $this->assertDatabaseHas('telegram_connections', [
            'user_id' => $user->getKey(),
            'telegraph_chat_id' => $chat->getKey(),
        ]);
    }

    public function test_invalid_link_does_not_store_telegram_chat(): void
    {
        $chat = $this->makeUnsavedChat('100002');

        try {
            $this->connectAccount->handle(str_repeat('a', 48), $chat);
            $this->fail('An invalid Telegram link was accepted.');
        } catch (InvalidTelegramLinkException) {
        }

        $this->assertFalse($chat->exists);
        $this->assertDatabaseMissing('telegraph_chats', [
            'chat_id' => '100002',
        ]);
        $this->assertDatabaseCount('telegram_connections', 0);
    }

    public function test_telegram_chat_cannot_be_connected_to_another_user(): void
    {
        $firstUser = User::factory()->create();
        $secondUser = User::factory()->create();
        $chat = $this->makeUnsavedChat('100003');

        $firstConnection = $this->connectAccount->handle(
            $this->createTokenFor($firstUser),
            $chat
        );

        $this->expectException(TelegramChatAlreadyConnectedException::class);

        try {
            $this->connectAccount->handle(
                $this->createTokenFor($secondUser),
                $chat
            );
        } finally {
            $this->assertSame($firstUser->getKey(), $firstConnection->refresh()->user_id);
            $this->assertDatabaseCount('telegram_connections', 1);
        }
    }

    public function test_user_can_replace_their_telegram_connection(): void
    {
        $user = User::factory()->create();
        $firstChat = $this->makeUnsavedChat('100004');
        $secondChat = $this->makeUnsavedChat('100005');

        $firstConnection = $this->connectAccount->handle(
            $this->createTokenFor($user),
            $firstChat
        );

        $secondConnection = $this->connectAccount->handle(
            $this->createTokenFor($user),
            $secondChat
        );

        $this->assertSame($firstConnection->getKey(), $secondConnection->getKey());
        $this->assertSame($secondChat->getKey(), $secondConnection->telegraph_chat_id);
        $this->assertDatabaseCount('telegram_connections', 1);
    }

    private function makeUnsavedChat(string $chatId): TelegraphChat
    {
        $bot = TelegraphBot::query()->create([
            'token' => 'test-token-' . $chatId,
            'name' => 'Test bot',
        ]);

        return $bot->chats()->make([
            'chat_id' => $chatId,
            'name' => 'Test chat',
        ]);
    }

    private function createTokenFor(User $user): string
    {
        $link = $this->createLink->handle($user);
        $queryString = parse_url($link, PHP_URL_QUERY);

        $this->assertIsString($queryString);

        parse_str($queryString, $query);

        $this->assertArrayHasKey('start', $query);
        $this->assertIsString($query['start']);

        return $query['start'];
    }
}
