<?php

namespace Tests\Feature\Inventory;

use App\Enums\HouseholdRole;
use App\Events\Inventory\ProductBecameLowStock;
use App\Listeners\Inventory\SendLowStockNotifications;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Inventory\LowStockNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendLowStockNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_low_stock_notification_is_sent_to_every_household_member(): void
    {
        Notification::fake();

        $household = Household::factory()->create(['name' => 'Test home']);
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();

        HouseholdMembership::factory()->owner()->for($household)->for($owner)->create();
        HouseholdMembership::factory()->for($household)->for($member)->create([
            'role' => HouseholdRole::Member,
        ]);

        $product = Product::factory()->volume()->create(['name' => 'Milk']);
        $householdProduct = HouseholdProduct::factory()
            ->for($household)
            ->for($product)
            ->create(['low_stock_threshold' => '1000.000']);
        $occurredAt = CarbonImmutable::parse('2026-08-22 10:00:00');

        app(SendLowStockNotifications::class)->handle(
            new ProductBecameLowStock(
                householdProductId: $householdProduct->getKey(),
                totalQuantity: '800.000',
                occurredAt: $occurredAt,
            ),
        );

        Notification::assertSentTo(
            [$owner, $member],
            LowStockNotification::class,
            function (LowStockNotification $notification, array $channels) use (
                $household,
                $product,
                $occurredAt,
            ): bool {
                return $channels === ['database', 'broadcast']
                    && $notification->toArray(new User) === [
                        'household_uuid' => $household->uuid,
                        'household_name' => 'Test home',
                        'product_uuid' => $product->uuid,
                        'product_name' => 'Milk',
                        'measurement_type' => 'volume',
                        'total_quantity' => '800.000',
                        'low_stock_threshold' => '1000.000',
                        'occurred_at' => $occurredAt->toISOString(),
                    ];
            },
        );
        Notification::assertNotSentTo($outsider, LowStockNotification::class);
        Notification::assertSentTimes(LowStockNotification::class, 2);
    }
}
