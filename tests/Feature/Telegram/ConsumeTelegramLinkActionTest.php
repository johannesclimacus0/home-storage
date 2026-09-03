<?php

namespace Tests\Feature\Telegram;

use App\Actions\Telegram\ConsumeTelegramLinkAction;
use App\Actions\Telegram\CreateTelegramLinkAction;
use App\Exceptions\Telegram\InvalidTelegramLinkException;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ConsumeTelegramLinkActionTest extends TestCase
{
    use RefreshDatabase;

    private CreateTelegramLinkAction $createLink;

    private ConsumeTelegramLinkAction $consumeLink;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.bot_username' => 'test_storage_bot',
            'cache.default' => 'array',
        ]);

        $this->createLink = app(CreateTelegramLinkAction::class);
        $this->consumeLink = app(ConsumeTelegramLinkAction::class);
    }

    public function test_it_consumes_telegram_link_and_returns_user(): void
    {
        $user = User::factory()->create();
        $token = $this->createTokenFor($user);
        $key = 'telegram-links:' . hash('sha256', $token);

        $resolvedUser = $this->consumeLink->handle($token);

        $this->assertTrue($resolvedUser->is($user));
        $this->assertFalse(Cache::has($key));
    }

    public function test_it_accepts_user_id_returned_from_cache_as_string(): void
    {
        $user = User::factory()->create();
        $token = str_repeat('a', 48);
        $key = 'telegram-links:' . hash('sha256', $token);

        Cache::put($key, (string) $user->getKey(), now()->addMinutes(10));

        $resolvedUser = $this->consumeLink->handle($token);

        $this->assertTrue($resolvedUser->is($user));
        $this->assertFalse(Cache::has($key));
    }

    public function test_telegram_link_cannot_be_consumed_twice(): void
    {
        $user = User::factory()->create();
        $token = $this->createTokenFor($user);

        $this->consumeLink->handle($token);

        $this->expectException(InvalidTelegramLinkException::class);

        $this->consumeLink->handle($token);
    }

    public function test_expired_telegram_link_cannot_be_consumed(): void
    {
        $user = User::factory()->create();
        $token = $this->createTokenFor($user);

        $this->travel(11)->minutes();

        $this->expectException(InvalidTelegramLinkException::class);

        $this->consumeLink->handle($token);
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
