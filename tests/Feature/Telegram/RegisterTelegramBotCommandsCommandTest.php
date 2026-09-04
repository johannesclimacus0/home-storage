<?php

namespace Tests\Feature\Telegram;

use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RegisterTelegramBotCommandsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_home_storage_commands_for_telegram_bot(): void
    {
        $telegraph = Telegraph::fake();
        TelegraphBot::query()->create([
            'token' => 'test-bot-token',
            'name' => 'Test bot',
        ]);

        $this->artisan('telegram:register-commands')
            ->expectsOutput('Commands registered for 1 bot(s).')
            ->assertSuccessful();

        $telegraph->assertSentData('setMyCommands', [
            'commands' => [
                ['command' => 'menu', 'description' => 'Главное меню'],
                ['command' => 'reminders', 'description' => 'Личные напоминания'],
                ['command' => 'shopping', 'description' => 'Список покупок'],
                ['command' => 'notifications', 'description' => 'Настройки уведомлений'],
                ['command' => 'cancel', 'description' => 'Отменить текущий ввод'],
                ['command' => 'help', 'description' => 'Помощь по командам'],
            ],
        ]);
    }
}
