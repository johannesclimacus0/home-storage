<?php

namespace Tests\Feature\Notifications;

use App\Contracts\Notifications\NotificationRepository;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class EloquentNotificationRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_paginates_only_user_notifications(): void
    {
        $user = User::factory()->create();

        $oldest = $this->createNotificationFor($user, 'Mlik');

        $this->travel(1)->second();

        $this->createNotificationFor($user, 'Braed');

        $this->travel(1)->second();

        $newest = $this->createNotificationFor($user, 'Milk');

        $otherUser = User::factory()->create();
        $foreignNotification = $this->createNotificationFor(
            $otherUser,
            'Pilk',
        );

        $repo = app(NotificationRepository::class);

        $paginator = $repo->paginateForUser($user, 2);

        $this->assertCount(2, $paginator->items());
        $this->assertSame(3, $paginator->total());
        $this->assertSame(2, $paginator->perPage());
        $this->assertSame(1, $paginator->currentPage());

        $this->assertSame(
            $newest->getKey(),
            $paginator->items()[0]->getKey(),
        );

        $returnedIds = collect($paginator->items())->pluck('id');

        $this->assertFalse(
            $returnedIds->contains($foreignNotification->getKey()),
        );

        $this->assertFalse(
            $returnedIds->contains($oldest->getKey()),
        );
    }

    public function test_repository_counts_only_user_unread_notifications(): void
    {
        $user = User::factory()->create();

        $firstUnreadNotification = $this->createNotificationFor(
            $user,
            'Mlik',
        );

        $readNotification = $this->createNotificationFor(
            $user,
            'Braed',
        );

        $readNotification->markAsRead();

        $secondUnreadNotification = $this->createNotificationFor(
            $user,
            'Milk',
        );

        $otherUser = User::factory()->create();

        $foreignUnreadNotification = $this->createNotificationFor(
            $otherUser,
            'Pilk',
        );

        $repo = app(NotificationRepository::class);

        $unreadCount = $repo->unreadCountForUser($user);

        $this->assertSame(2, $unreadCount);
        $this->assertNotNull($readNotification->refresh()->read_at);
        $this->assertNull($firstUnreadNotification->refresh()->read_at);
        $this->assertNull($secondUnreadNotification->refresh()->read_at);
        $this->assertNull($foreignUnreadNotification->refresh()->read_at);
    }

    public function test_repository_finds_notification_owned_by_user(): void
    {
        $user = User::factory()->create();
        $notification = $this->createNotificationFor($user, 'Mlik');

        $repo = app(NotificationRepository::class);

        $foundNotification = $repo->findForUser(
            $user,
            $notification->getKey(),
        );

        $this->assertInstanceOf(
            DatabaseNotification::class,
            $foundNotification,
        );

        $this->assertSame(
            $notification->getKey(),
            $foundNotification->getKey(),
        );

        $this->assertSame(
            $notification->data['product_uuid'],
            $foundNotification->data['product_uuid'],
        );

        $this->assertSame(
            'Mlik',
            $foundNotification->data['product_name'],
        );
    }

    public function test_repository_does_not_find_another_user_notification(): void
    {
        $user = User::factory()->create();
        $anotherUser = User::factory()->create();

        $foreignNotification = $this->createNotificationFor(
            $anotherUser,
            'Pilk',
        );

        $repo = app(NotificationRepository::class);

        $this->expectException(ModelNotFoundException::class);

        $repo->findForUser(
            $user,
            $foreignNotification->getKey(),
        );
    }

    public function test_repository_marks_notification_as_read(): void
    {
        $user = User::factory()->create();
        $notification = $this->createNotificationFor($user, 'Milk');

        $repo = app(NotificationRepository::class);

        $this->assertNull($notification->read_at);

        $repo->markAsRead($notification);

        $firstReadAt = $notification->refresh()->read_at;

        $this->assertNotNull($firstReadAt);

        $this->travel(1)->hour();

        $repo->markAsRead($notification);

        $this->assertTrue(
            $firstReadAt->equalTo($notification->refresh()->read_at),
        );
    }

    public function test_repository_marks_all_user_notifications_as_read(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $firstUnreadNotification = $this->createNotificationFor(
            $user,
            'Mlik',
        );

        $secondUnreadNotification = $this->createNotificationFor(
            $user,
            'Milk',
        );

        $alreadyReadNotification = $this->createNotificationFor(
            $user,
            'Braed',
        );

        $foreignNotification = $this->createNotificationFor(
            $otherUser,
            'Pilk',
        );

        $alreadyReadNotification->markAsRead();
        $originalReadAt = $alreadyReadNotification->refresh()->read_at;

        $this->assertNotNull($originalReadAt);

        $this->travel(1)->hour();

        $repo = app(NotificationRepository::class);

        $updatedCount = $repo->markAllAsRead($user);

        $this->assertSame(2, $updatedCount);
        $this->assertSame(0, $repo->unreadCountForUser($user));
        $this->assertSame(1, $repo->unreadCountForUser($otherUser));

        $this->assertNotNull(
            $firstUnreadNotification->refresh()->read_at,
        );

        $this->assertNotNull(
            $secondUnreadNotification->refresh()->read_at,
        );

        $this->assertNull(
            $foreignNotification->refresh()->read_at,
        );

        $this->assertTrue(
            $originalReadAt->equalTo(
                $alreadyReadNotification->refresh()->read_at,
            ),
        );
    }

    private function createNotificationFor(
        User $user,
        string $productName = 'Milk',
    ): DatabaseNotification {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'test',
            'data' => [
                'product_uuid' => (string) Str::uuid(),
                'product_name' => $productName,
            ],
        ]);
    }
}
