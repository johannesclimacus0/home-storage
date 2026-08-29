<?php

namespace Tests\Feature\Inventory;

use App\Events\Inventory\ProductBecameLowStock;
use App\Listeners\SendProductLowStockNotification;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\LowStockNotificationState;
use App\Models\Product;
use App\Models\User;
use App\Notifications\Inventory\ProductLowStockNotification;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

final class SendProductLowStockNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_notification_delivery_is_queued_for_every_household_member(): void
    {
        Queue::fake();

        $household = Household::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();

        HouseholdMembership::factory()
            ->owner()
            ->for($household)
            ->for($owner)
            ->create();

        HouseholdMembership::factory()
            ->for($household)
            ->for($member)
            ->create();

        $product = Product::factory()->volume()->create();
        $householdProduct = HouseholdProduct::factory()
            ->for($household)
            ->for($product)
            ->create();

        app(SendProductLowStockNotification::class)->handle(
            new ProductBecameLowStock(
                householdProductId: $householdProduct->getKey(),
                totalQuantity: '800.000',
                occurredAt: CarbonImmutable::parse('2026-08-28 09:00:00', 'UTC'),
            ),
        );

        Queue::assertPushed(SendQueuedNotifications::class, 4);
        Queue::assertPushed(
            SendQueuedNotifications::class,
            fn (SendQueuedNotifications $job): bool => $job->notification instanceof ProductLowStockNotification
                && in_array($job->channels, [['database'], ['broadcast']], true)
                && $job->queue === 'notifications'
                && $job->tries === 3
                && $job->timeout === 15
                && $job->failOnTimeout === true
                && $job->backoff() === [10, 60]
                && $job->notifiables->first()->is($owner),
        );
        Queue::assertPushed(
            SendQueuedNotifications::class,
            fn (SendQueuedNotifications $job): bool => $job->notification instanceof ProductLowStockNotification
                && in_array($job->channels, [['database'], ['broadcast']], true)
                && $job->queue === 'notifications'
                && $job->tries === 3
                && $job->timeout === 15
                && $job->failOnTimeout === true
                && $job->backoff() === [10, 60]
                && $job->notifiables->first()->is($member),
        );
        $this->assertDatabaseCount('notifications', 0);
    }

    public function test_notification_is_stored_for_every_household_member(): void
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $outsider = User::factory()->create();

        HouseholdMembership::factory()
            ->owner()
            ->for($household)
            ->for($owner)
            ->create();

        HouseholdMembership::factory()
            ->for($household)
            ->for($member)
            ->create();

        $product = Product::factory()->volume()->create(['name' => 'Test']);
        $householdProduct = HouseholdProduct::factory()
            ->for($household)
            ->for($product)
            ->create(['low_stock_threshold' => '1000.000']);

        $occurredAt = CarbonImmutable::parse('2026-08-28 09:00:00', 'UTC');

        app(SendProductLowStockNotification::class)->handle(
            new ProductBecameLowStock(
                householdProductId: $householdProduct->getKey(),
                totalQuantity: '800.000',
                occurredAt: $occurredAt,
            ),
        );

        $this->assertDatabaseCount('notifications', 2);
        $this->assertCount(1, $owner->notifications);
        $this->assertCount(1, $member->notifications);
        $this->assertCount(0, $outsider->notifications);

        $ownerNotification = $owner->notifications()->sole();
        $memberNotification = $member->notifications()->sole();

        $this->assertSame(
            ProductLowStockNotification::class,
            $ownerNotification->type,
        );
        $this->assertSame($ownerNotification->data, $memberNotification->data);
        $this->assertSame([
            'household_uuid' => $household->uuid,
            'household_name' => $household->name,
            'product_uuid' => $product->uuid,
            'product_name' => 'Test',
            'measurement_type' => 'volume',
            'quantity' => '800.000',
            'threshold' => '1000.000',
            'became_low_at' => $occurredAt->toIso8601String(),
        ], $ownerNotification->data);
        $this->assertNull($ownerNotification->read_at);
        $this->assertNull($memberNotification->read_at);
    }

    public function test_initial_notification_time_is_recorded_for_every_household_member(): void
    {
        Queue::fake();

        $household = Household::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();
        $ownerMembership = HouseholdMembership::factory()
            ->owner()
            ->for($household)
            ->for($owner)
            ->create();
        $memberMembership = HouseholdMembership::factory()
            ->for($household)
            ->for($member)
            ->create();
        $product = Product::factory()->volume()->create();
        $householdProduct = HouseholdProduct::factory()
            ->for($household)
            ->for($product)
            ->create();
        $occurredAt = CarbonImmutable::parse('2026-08-28 09:00:00', 'UTC');

        app(SendProductLowStockNotification::class)->handle(
            new ProductBecameLowStock(
                householdProductId: $householdProduct->getKey(),
                totalQuantity: '800.000',
                occurredAt: $occurredAt,
            ),
        );

        $this->assertDatabaseCount('low_stock_notification_states', 2);

        foreach ([$ownerMembership, $memberMembership] as $membership) {
            $state = LowStockNotificationState::query()
                ->where('household_membership_id', $membership->getKey())
                ->where('household_product_id', $householdProduct->getKey())
                ->sole();

            $this->assertTrue($state->last_notified_at->equalTo($occurredAt));
        }
    }
}
