<?php

namespace Tests\Feature\Messages;

use App\Events\Messages\HouseholdMessageSent;
use App\Models\Household;
use App\Models\HouseholdMessage;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdMessageSentTest extends TestCase
{
    use RefreshDatabase;

    public function test_message_is_broadcast_on_private_household_channel(): void
    {
        $household = Household::factory()->create();
        $message = HouseholdMessage::factory()
            ->for($household)
            ->create();
        $event = new HouseholdMessageSent($message);

        $this->assertInstanceOf(PrivateChannel::class, $event->broadcastOn());
        $this->assertSame('private-households.' . $household->uuid, $event->broadcastOn()->name);
    }

    public function test_message_event_has_expected_broadcast_name(): void
    {
        $message = HouseholdMessage::factory()->create();

        $event = new HouseholdMessageSent($message);

        $this->assertSame('household.message.sent', $event->broadcastAs());
    }

    public function test_message_event_broadcasts_expected_payload(): void
    {
        $sender = User::factory()->create([
            'name' => 'Test Name',
            'email' => 'sender@example.test'
        ]);
        $message = HouseholdMessage::factory()
            ->for($sender, 'sender')
            ->create(['content' => 'Test message']);

        $event = new HouseholdMessageSent($message);
        $payload = $event->broadcastWith();

        $this->assertSame($message->uuid, $payload['message']['uuid']);
        $this->assertSame('Test message', $payload['message']['content']);
        $this->assertSame($sender->getKey(), $payload['message']['sender']['id']);
        $this->assertSame('Test Name', $payload['message']['sender']['name']);
        $this->assertNull($payload['message']['edited_at']);
        $this->assertNull($payload['message']['deleted_at']);
        $this->assertSame($message->created_at->toISOString(), $payload['message']['created_at']);
        $this->assertArrayNotHasKey('email', $payload['message']['sender']);
    }
}
