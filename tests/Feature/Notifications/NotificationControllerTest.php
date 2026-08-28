<?php

namespace Tests\Feature\Notifications;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class NotificationControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_list_only_their_notifications(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->createNotificationFor($user);
        $this->travel(1)->second();
        $this->createNotificationFor($user, 'mlik');

        $otherUser = User::factory()->create(['email_verified_at' => now()]);

        $foreignNotification = $this->createNotificationFor(
            $otherUser,
            'Pilk',
        );

        $response = $this->actingAs($user)
            ->getJson('/api/notifications?per_page=1');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'uuid',
                    'type',
                    'data',
                    'read_at',
                    'created_at',
                ],
            ],
            'links',
            'meta',
        ]);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonPath('data.0.read_at', null);
        $response->assertJsonPath('meta.total', 2);
        $response->assertJsonPath('meta.per_page', 1);
        $response->assertJsonPath(
            'data.0.data.product_name',
            'mlik'
        );
        $returnedUuids = collect($response->json('data'))
            ->pluck('uuid');
        $this->assertFalse(
            $returnedUuids->contains($foreignNotification->getKey()),
        );
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidPerPageValues(): array
    {
        return [
            'less than 1' => [0],
            'greater than 100' => [101],
            'not integer' => ['meow'],
        ];
    }

    #[DataProvider('invalidPerPageValues')]
    public function test_notifications_index_validates_per_page(mixed $invalidPerPage): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $response = $this->actingAs($user)->getJson(
            '/api/notifications?per_page=' . $invalidPerPage,
        );

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('per_page');
    }

    public function test_guest_cannot_list_notifications(): void
    {
        $response = $this->getJson('/api/notifications');
        $response->assertUnauthorized();
    }

    public function test_notifications_index_accepts_maximum_per_page(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->getJson('/api/notifications?per_page=100')
            ->assertOk()
            ->assertJsonPath('meta.per_page', 100);
    }

    public function test_verified_user_can_mark_owned_notification_as_read(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $notification = $this->createNotificationFor($user);

        $this->assertNull($notification->read_at);

        $response = $this->actingAs($user)
            ->patchJson("/api/notifications/{$notification->getKey()}/read");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'uuid',
                'type',
                'data',
                'read_at',
                'created_at',
            ],
        ]);

        $response->assertJsonPath('data.uuid', $notification->getKey());
        $this->assertNotNull($response->json('data.read_at'));
        $this->assertNotNull($notification->refresh()->read_at);
    }

    public function test_user_cannot_mark_foreign_notification_as_read(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);

        $foreignNotification = $this->createNotificationFor(
            $otherUser,
            'Pilk',
        );

        $response = $this->actingAs($user)->patchJson(
            "/api/notifications/{$foreignNotification->getKey()}/read",
        );

        $response->assertNotFound();
        $this->assertNull($foreignNotification->refresh()->read_at);
    }

    public function test_verified_user_can_mark_all_own_notifications_as_read(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $otherUser = User::factory()->create(['email_verified_at' => now()]);

        $firstNotification = $this->createNotificationFor($user, 'Milk');
        $secondNotification = $this->createNotificationFor($user, 'Mlik');
        $foreignNotification = $this->createNotificationFor($otherUser, 'Pilk');

        $response = $this->actingAs($user)
            ->patchJson('/api/notifications/read-all');

        $response->assertOk();
        $response->assertJsonPath('updated_count', 2);

        $this->assertNotNull($firstNotification->refresh()->read_at);
        $this->assertNotNull($secondNotification->refresh()->read_at);
        $this->assertNull($foreignNotification->refresh()->read_at);

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
