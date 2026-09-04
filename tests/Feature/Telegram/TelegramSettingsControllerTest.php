<?php

namespace Tests\Feature\Telegram;

use App\Models\TelegramConnection;
use App\Models\User;
use DefStudio\Telegraph\Models\TelegraphBot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_manage_telegram_subscriptions(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);

        $this->actingAs($user)
            ->getJson('/api/telegram/subscriptions')
            ->assertOk()
            ->assertJsonPath('data.0.key', 'low_stock')
            ->assertJsonPath('data.0.enabled', false);

        $this->actingAs($user)
            ->putJson('/api/telegram/subscriptions', [
                'subscriptions' => ['low_stock'],
            ])
            ->assertOk()
            ->assertJsonPath('data.0.enabled', true);

        $this->assertDatabaseHas('telegram_notification_subscriptions', [
            'user_id' => $user->getKey(),
            'type' => 'low_stock',
        ]);

        $this->actingAs($user)
            ->putJson('/api/telegram/subscriptions', [
                'subscriptions' => [],
            ])
            ->assertOk()
            ->assertJsonPath('data.0.enabled', false);

        $this->assertDatabaseMissing('telegram_notification_subscriptions', [
            'user_id' => $user->getKey(),
            'type' => 'low_stock',
        ]);
    }

    public function test_user_can_create_and_delete_own_reminder(): void
    {
        $user = User::factory()->create(['email_verified_at' => now()]);
        $this->connectTelegram($user);
        $remindAt = now()->addHour()->toIso8601String();

        $response = $this->actingAs($user)
            ->postJson('/api/telegram/reminders', [
                'message' => 'Buy milk',
                'remind_at' => $remindAt,
                'frequency' => 'daily',
            ])
            ->assertCreated()
            ->assertJsonPath('data.message', 'Buy milk')
            ->assertJsonPath('data.frequency', 'daily');

        $uuid = $response->json('data.uuid');

        $this->actingAs($user)
            ->getJson('/api/telegram/reminders')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        $this->actingAs($user)
            ->deleteJson('/api/telegram/reminders/' . $uuid)
            ->assertNoContent();

        $this->assertDatabaseMissing('telegram_reminders', ['uuid' => $uuid]);
    }

    public function test_user_cannot_access_another_users_reminder(): void
    {
        $owner = User::factory()->create(['email_verified_at' => now()]);
        $outsider = User::factory()->create(['email_verified_at' => now()]);
        $reminder = $owner->telegramReminders()->create([
            'message' => 'Private reminder',
            'remind_at' => now()->addHour(),
        ]);

        $this->actingAs($outsider)
            ->deleteJson('/api/telegram/reminders/' . $reminder->uuid)
            ->assertNotFound();
    }

    private function connectTelegram(User $user): void
    {
        $bot = TelegraphBot::query()->create([
            'token' => 'test-bot-token',
            'name' => 'Test bot',
        ]);
        $chat = $bot->chats()->create([
            'chat_id' => '100001',
            'name' => 'Test chat',
        ]);

        TelegramConnection::query()->create([
            'user_id' => $user->getKey(),
            'telegraph_chat_id' => $chat->getKey(),
            'linked_at' => now(),
        ]);
    }
}
