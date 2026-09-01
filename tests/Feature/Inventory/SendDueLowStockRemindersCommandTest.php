<?php

namespace Tests\Feature\Inventory;

use App\Contracts\Inventory\LowStockReminderRepository;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

final class SendDueLowStockRemindersCommandTest extends TestCase
{
    public function test_command_reports_number_of_queued_reminders(): void
    {
        $repository = Mockery::mock(LowStockReminderRepository::class);
        $repository->shouldReceive('dueAt')
            ->once()
            ->andReturn(new Collection);
        $this->app->instance(LowStockReminderRepository::class, $repository);

        $this->artisan('inventory:send-low-stock-reminders')
            ->expectsOutput('Queued 0 low-stock reminders.')
            ->assertSuccessful();
    }
}
