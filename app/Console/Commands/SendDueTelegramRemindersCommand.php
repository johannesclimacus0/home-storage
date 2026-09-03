<?php

namespace App\Console\Commands;

use App\Actions\Telegram\SendDueTelegramRemindersAction;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class SendDueTelegramRemindersCommand extends Command
{
    protected $signature = 'telegram:send-due-reminders';

    protected $description = 'Queue due personal Telegram reminders';

    public function handle(SendDueTelegramRemindersAction $action): int
    {
        $count = $action->handle(CarbonImmutable::now());

        $this->info("Queued {$count} Telegram reminders.");

        return self::SUCCESS;
    }
}
