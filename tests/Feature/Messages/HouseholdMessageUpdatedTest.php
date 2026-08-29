<?php

namespace Tests\Feature\Messages;

use App\Events\Messages\HouseholdMessageUpdated;
use App\Models\Household;
use App\Models\HouseholdMessage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdMessageUpdatedTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_is_broadcast_on_private_household_channel(): void
    {
        $household = Household::factory()->create();
        $message = HouseholdMessage::factory()
            ->for($household)
            ->create();
        $event = new HouseholdMessageUpdated($message);

        $this->assertInstanceOf(PrivateChannel::class, $event->broadcastOn());
        $this->assertSame('private-households.' . $household->uuid, $event->broadcastOn()->name);
    }

    public function test_message_event_has_expected_broadcast_name(): void
    {
        $message = HouseholdMessage::factory()->create();

        $event = new HouseholdMessageUpdated($message);

        $this->assertSame('household.message.updated', $event->broadcastAs());
    }

    public function test_message_event_broadcasts_expected_payload(): void
    {
        $sender = User::factory()->create([
            'name' => 'Test Name',
            'email' => 'sender@example.test',
        ]);
        $message = HouseholdMessage::factory()
            ->for($sender, 'sender')
            ->create([
                'content' => 'Test message',
                'edited_at' => CarbonImmutable::parse('2026-08-29 14:00:00'),
            ]);

        $event = new HouseholdMessageUpdated($message);
        $payload = $event->broadcastWith();

        $this->assertSame($message->uuid, $payload['message']['uuid']);
        $this->assertSame('Test message', $payload['message']['content']);
        $this->assertSame($message->edited_at->toISOString(), $payload['message']['edited_at']);
    }
}
