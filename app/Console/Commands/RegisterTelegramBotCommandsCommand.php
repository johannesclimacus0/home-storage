<?php

namespace App\Console\Commands;

use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Console\Command;

final class RegisterTelegramBotCommandsCommand extends Command
{
    protected $signature = 'telegram:register-commands';

    protected $description = 'Register bot commands';

    public function handle(): int
    {
        $bots = TelegraphBot::query()->get();

        if ($bots->isEmpty()) {
            $this->error('Telegram bot is not configured.');

            return self::FAILURE;
        }

        foreach ($bots as $bot) {
            $bot->registerCommands([
                'menu' => 'Главное меню',
                'reminders' => 'Личные напоминания',
                'shopping' => 'Список покупок',
                'notifications' => 'Настройки уведомлений',
                'cancel' => 'Отменить текущий ввод',
                'help' => 'Помощь по командам',
            ])->send();
        }

        $this->info("Commands registered for {$bots->count()} bot(s).");

        return self::SUCCESS;
    }
}
