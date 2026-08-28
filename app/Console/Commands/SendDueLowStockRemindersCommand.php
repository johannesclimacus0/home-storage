<?php

namespace App\Console\Commands;

use App\Actions\Inventory\SendDueLowStockRemindersAction;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;

final class SendDueLowStockRemindersCommand extends Command
{
    protected $signature = 'inventory:send-low-stock-reminders';

    protected $description = 'Send due low-stock reminders';

    public function handle(SendDueLowStockRemindersAction $action): int
    {
        $sentCount = $action->handle(CarbonImmutable::now());

        $this->info("Queued {$sentCount} low-stock reminders.");

        return self::SUCCESS;
    }
}
