<?php

namespace Tests\Feature\Messages;

use App\Actions\Messages\SendMessageAction;
use App\DTO\Messages\SendMessageData;
use App\Events\Messages\HouseholdMessageSent;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Event;
use InvalidArgumentException;
use Tests\TestCase;

class MessageActionsTest extends TestCase
{
    use DatabaseMigrations;

    public function test_sending_message_dispatches_household_message_sent_event(): void
    {
        Event::fake([HouseholdMessageSent::class]);

        $household = Household::factory()->create();
        $sender = User::factory()->create();

        HouseholdMembership::factory()
            ->for($household)
            ->for($sender, 'user')
            ->create();

        $data = new SendMessageData(
            householdUuid: $household->uuid,
            senderId: $sender->getKey(),
            content: 'Test message'
        );

        $message = app(SendMessageAction::class)->handle($data);

        $this->assertDatabaseHas('household_messages', [
            'id' => $message->getKey(),
            'household_id' => $household->getKey(),
            'sender_id' => $sender->getKey(),
            'content' => 'Test message'
        ]);

        Event::assertDispatched(
            HouseholdMessageSent::class,
            function (HouseholdMessageSent $event) use ($message): bool {
                return $event->message->is($message);
            }
        );

        Event::assertDispatchedTimes(HouseholdMessageSent::class, 1);
    }

    public function test_sending_message_trims_content(): void
    {
        Event::fake([HouseholdMessageSent::class]);

        $household = Household::factory()->create();
        $sender = User::factory()->create();

        HouseholdMembership::factory()
            ->for($household)
            ->for($sender, 'user')
            ->create();

        $message = app(SendMessageAction::class)->handle(
            new SendMessageData(
                householdUuid: $household->uuid,
                senderId: $sender->getKey(),
                content: '  Test message  '
            )
        );

        $this->assertSame('Test message', $message->content);
        $this->assertDatabaseHas('household_messages', [
            'id' => $message->getKey(),
            'content' => 'Test message'
        ]);
    }

    public function test_sending_empty_message_throws_exception(): void
    {
        Event::fake([HouseholdMessageSent::class]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Message content cannot be empty.');

        try {
            app(SendMessageAction::class)->handle(
                new SendMessageData(
                    householdUuid: 'unused-household-uuid',
                    senderId: 1,
                    content: '   '
                )
            );
        } finally {
            Event::assertNotDispatched(HouseholdMessageSent::class);
            $this->assertDatabaseCount('household_messages', 0);
        }
    }

    public function test_user_cannot_send_message_without_household_membership(): void
    {
        Event::fake([HouseholdMessageSent::class]);

        $household = Household::factory()->create();
        $outsider = User::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        try {
            app(SendMessageAction::class)->handle(
                new SendMessageData(
                    householdUuid: $household->uuid,
                    senderId: $outsider->getKey(),
                    content: 'Test message'
                )
            );
        } finally {
            Event::assertNotDispatched(HouseholdMessageSent::class);
            $this->assertDatabaseCount('household_messages', 0);
        }
    }
}
