<?php

namespace Tests\Feature\Notifications;

use App\Actions\Notifications\ListNotificationsAction;
use App\Actions\Notifications\MarkAllNotificationsAsReadAction;
use App\Actions\Notifications\MarkNotificationAsReadAction;
use App\Models\User;
use App\Notifications\Inventory\LowStockNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_notifications_action_returns_only_user_notifications(): void
    {
        $user = User::factory()->create();
        $firstNotification = $this->createNotificationFor($user);
        $secondNotification = $this->createNotificationFor($user);
        $otherUser = User::factory()->create();
        $foreignNotification = $this->createNotificationFor($otherUser);

        $list = $this->app->make(ListNotificationsAction::class)->handle($user, 15);

        self::assertCount(2, $list);
        self::assertTrue($list->contains($firstNotification));
        self::assertTrue($list->contains($secondNotification));
        self::assertFalse($list->contains($otherUser));
    }
    public function test_mark_notification_as_read_action_marks_owned_notification(): void
    {
        $user = User::factory()->create();
        $firstNotification = $this->createNotificationFor($user);

        $this->assertNull($firstNotification->read_at);

        $action = $this->app->make(MarkNotificationAsReadAction::class);
        $result = $action->handle($user, $firstNotification->getKey());

        $this->assertSame($firstNotification->getKey(), $result->getKey());
        $this->assertNotNull($firstNotification->refresh()->read_at);
        $this->assertNotNull($result->read_at);
    }
    public function test_mark_notification_as_read_action_rejects_foreign_notification(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $foreignNotification = $this->createNotificationFor($otherUser);

        $action = $this->app->make(MarkNotificationAsReadAction::class);

        $this->expectException(ModelNotFoundException::class);

        $action->handle($user, $foreignNotification->getKey());
    }
    public function test_mark_all_notifications_as_read_action_marks_only_user_notifications(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $firstNotification = $this->createNotificationFor($user);
        $secondNotification = $this->createNotificationFor($user);
        $foreignNotification = $this->createNotificationFor($otherUser);

        $this->app->make(MarkAllNotificationsAsReadAction::class)->handle($user);

        $this->assertNotNull($firstNotification->refresh()->read_at);
        $this->assertNotNull($secondNotification->refresh()->read_at);
        $this->assertNull($foreignNotification->refresh()->read_at);
    }
    private function createNotificationFor(
        User $user,
        string $productName = 'Milk',
    ): DatabaseNotification {
        $existingIds = $user->notifications()
            ->pluck('id');

        $user->notifyNow(
            new LowStockNotification(
                householdUuid: (string) Str::uuid(),
                householdName: 'Test household',
                productUuid: (string) Str::uuid(),
                productName: $productName,
                measurementType: 'volume',
                totalQuantity: '800.000',
                lowStockThreshold: '1000.000',
                occurredAt: CarbonImmutable::parse(
                    '2026-08-23 10:00:00',
                ),
            ),
            ['database'],
        );

        return $user->notifications()
            ->whereNotIn('id', $existingIds)
            ->firstOrFail();
    }
}
