<?php

namespace Tests\Feature\Inventory;

use App\Actions\Inventory\SendDueLowStockRemindersAction;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\LowStockNotificationState;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Inventory\ProductLowStockNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class SendDueLowStockRemindersActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_due_low_stock_reminder_is_sent_and_rescheduled(): void
    {
        Notification::fake();

        $now = CarbonImmutable::parse('2026-08-28 12:00:00', 'UTC');
        $household = Household::factory()->create();
        $user = User::factory()->create();
        $membership = HouseholdMembership::factory()
            ->for($household)
            ->for($user)
            ->create([
                'low_stock_reminders_enabled' => true,
                'low_stock_reminder_interval_hours' => 24,
            ]);
        $product = Product::factory()->volume()->create();
        $householdProduct = HouseholdProduct::factory()
            ->for($household)
            ->for($product)
            ->create([
                'low_stock_since' => $now->subDays(2),
            ]);
        $state = LowStockNotificationState::factory()->create([
            'household_membership_id' => $membership->getKey(),
            'household_product_id' => $householdProduct->getKey(),
            'last_notified_at' => $now->subHours(25),
        ]);

        $sentCount = app(SendDueLowStockRemindersAction::class)->handle($now);

        $this->assertSame(1, $sentCount);
        Notification::assertSentTo($user, ProductLowStockNotification::class);
        $this->assertTrue($state->refresh()->last_notified_at->equalTo($now));
    }
}
