<?php

namespace Tests\Feature\Telegram;

use App\Actions\Telegram\CreateTelegramLinkAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CreateTelegramLinkActionTest extends TestCase
{
    use RefreshDatabase;

    private CreateTelegramLinkAction $action;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_username' => 'test_storage_bot',
            'cache.default' => 'array',
        ]);

        $this->action = app(CreateTelegramLinkAction::class);
    }

    public function test_it_creates_temporary_telegram_link_for_user(): void
    {
        $user = User::factory()->create();
        $link = $this->action->handle($user);

        $queryString = parse_url($link, PHP_URL_QUERY);

        $this->assertIsString($queryString);

        parse_str($queryString, $query);

        $this->assertArrayHasKey('start', $query);
        $this->assertIsString($query['start']);

        $token = $query['start'];
        $key = 'telegram-links:' . hash('sha256', $token);

        $this->assertSame('t.me', parse_url($link, PHP_URL_HOST));
        $this->assertSame('/test_storage_bot', parse_url($link, PHP_URL_PATH));
        $this->assertSame(48, strlen($token));
        $this->assertSame($user->getKey(), Cache::get($key));

        $this->travel(11)->minutes();

        $this->assertNull(Cache::get($key));
    }

    public function test_it_normalizes_bot_username_in_link(): void
    {
        config(['services.telegram.bot_username' => '@test_storage_bot']);

        $link = $this->action->handle(User::factory()->create());

        $this->assertStringStartsWith('https://t.me/test_storage_bot?start=', $link);
    }
}
