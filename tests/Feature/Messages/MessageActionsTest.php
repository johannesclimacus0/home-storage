<?php

namespace Tests\Feature\Messages;

use App\Actions\Messages\DeleteMessageAction;
use App\Actions\Messages\SendMessageAction;
use App\Actions\Messages\UpdateMessageAction;
use App\DTO\Messages\SendMessageData;
use App\DTO\Messages\UpdateMessageData;
use App\Events\Messages\HouseholdMessageDeleted;
use App\Events\Messages\HouseholdMessageSent;
use App\Events\Messages\HouseholdMessageUpdated;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdMessage;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
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

    public function test_updating_message_dispatches_household_message_updated_event(): void
    {
        Event::fake([HouseholdMessageUpdated::class]);

        $household = Household::factory()->create();
        $sender = User::factory()->create();

        HouseholdMembership::factory()
            ->for($household)
            ->for($sender, 'user')
            ->create();

        $message = HouseholdMessage::factory()
            ->for($household)
            ->for($sender, 'sender')
            ->create([
                'content' => 'Test message'
            ]);

        $data = new UpdateMessageData(
            householdUuid: $household->uuid,
            actorUserId: $sender->getKey(),
            messageUuid: $message->uuid,
            content: 'Updated message'
        );

        $updatedMessage = app(UpdateMessageAction::class)->handle($data);

        $this->assertDatabaseHas('household_messages', [
            'id' => $message->getKey(),
            'household_id' => $household->getKey(),
            'sender_id' => $sender->getKey(),
            'content' => 'Updated message'
        ]);

        $this->assertSame('Updated message', $updatedMessage->content);
        $this->assertNotNull($updatedMessage->edited_at);

        Event::assertDispatched(
            HouseholdMessageUpdated::class,
            function (HouseholdMessageUpdated $event) use ($updatedMessage): bool {
                return $event->message->is($updatedMessage)
                    && $event->message->content === 'Updated message'
                    && $event->message->edited_at !== null;
            }
        );

        Event::assertDispatchedTimes(HouseholdMessageUpdated::class, 1);
    }
    public function test_deleting_message_dispatches_household_message_deleted_event(): void
    {
        Event::fake([HouseholdMessageDeleted::class]);

        $household = Household::factory()->create();
        $sender = User::factory()->create();

        HouseholdMembership::factory()
            ->for($household)
            ->for($sender, 'user')
            ->create();
        $message = HouseholdMessage::factory()
            ->for($household)
            ->for($sender, 'sender')
            ->create([
                'content' => 'Test message'
            ]);

        app(DeleteMessageAction::class)->handle(
            householdUuid: $household->uuid,
            actorUserId: $sender->getKey(),
            messageUuid: $message->uuid
        );

        $this->assertSoftDeleted('household_messages', [
            'id' => $message->getKey(),
            'household_id' => $household->getKey(),
            'sender_id' => $sender->getKey(),
            'content' => 'Test message'
        ]);

        Event::assertDispatched(
            HouseholdMessageDeleted::class,
            function (HouseholdMessageDeleted $event) use ($message): bool {
                return $event->message->is($message)
                    && $event->message->trashed()
                    && $event->message->deleted_at !== null;
            }
        );

        Event::assertDispatchedTimes(HouseholdMessageDeleted::class, 1);
    }

    public function test_updating_message_with_empty_content_does_not_dispatch_event(): void
    {
        Event::fake([HouseholdMessageUpdated::class]);

        $household = Household::factory()->create();
        $sender = User::factory()->create();

        HouseholdMembership::factory()
            ->for($household)
            ->for($sender, 'user')
            ->create();

        $message = HouseholdMessage::factory()
            ->for($household)
            ->for($sender, 'sender')
            ->create([
                'content' => 'Original message'
            ]);

        $this->expectException(InvalidArgumentException::class);

        try {
            app(UpdateMessageAction::class)->handle(
                new UpdateMessageData(
                    householdUuid: $household->uuid,
                    actorUserId: $sender->getKey(),
                    messageUuid: $message->uuid,
                    content: '   '
                )
            );
        } finally {
            $this->assertDatabaseHas('household_messages', [
                'id' => $message->getKey(),
                'content' => 'Original message',
                'edited_at' => null
            ]);
            Event::assertNotDispatched(HouseholdMessageUpdated::class);
        }
    }

    public function test_user_cannot_update_another_members_message(): void
    {
        Event::fake([HouseholdMessageUpdated::class]);

        $household = Household::factory()->create();
        $sender = User::factory()->create();
        $otherMember = User::factory()->create();

        HouseholdMembership::factory()
            ->for($household)
            ->for($sender, 'user')
            ->create();
        HouseholdMembership::factory()
            ->for($household)
            ->for($otherMember, 'user')
            ->create();

        $message = HouseholdMessage::factory()
            ->for($household)
            ->for($sender, 'sender')
            ->create([
                'content' => 'Original message'
            ]);

        $this->expectException(AuthorizationException::class);

        try {
            app(UpdateMessageAction::class)->handle(
                new UpdateMessageData(
                    householdUuid: $household->uuid,
                    actorUserId: $otherMember->getKey(),
                    messageUuid: $message->uuid,
                    content: 'Unauthorized update'
                )
            );
        } finally {
            $this->assertDatabaseHas('household_messages', [
                'id' => $message->getKey(),
                'content' => 'Original message',
                'edited_at' => null
            ]);
            Event::assertNotDispatched(HouseholdMessageUpdated::class);
        }
    }

    public function test_user_cannot_delete_another_members_message(): void
    {
        Event::fake([HouseholdMessageDeleted::class]);

        $household = Household::factory()->create();
        $sender = User::factory()->create();
        $otherMember = User::factory()->create();

        HouseholdMembership::factory()
            ->for($household)
            ->for($sender, 'user')
            ->create();
        HouseholdMembership::factory()
            ->for($household)
            ->for($otherMember, 'user')
            ->create();

        $message = HouseholdMessage::factory()
            ->for($household)
            ->for($sender, 'sender')
            ->create();

        $this->expectException(AuthorizationException::class);

        try {
            app(DeleteMessageAction::class)->handle(
                householdUuid: $household->uuid,
                actorUserId: $otherMember->getKey(),
                messageUuid: $message->uuid
            );
        } finally {
            $this->assertDatabaseHas('household_messages', [
                'id' => $message->getKey(),
                'deleted_at' => null
            ]);
            Event::assertNotDispatched(HouseholdMessageDeleted::class);
        }
    }
}
