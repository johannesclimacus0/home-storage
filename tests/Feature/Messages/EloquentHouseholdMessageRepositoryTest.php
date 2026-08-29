<?php

namespace Tests\Feature\Messages;

use App\Contracts\Messages\MessageRepository;
use App\Models\Household;
use App\Models\HouseholdMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentHouseholdMessageRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_creates_message_for_household_and_sender(): void
    {
        $household = Household::factory()->create();
        $sender = User::factory()->create();
        $repository = app(MessageRepository::class);

        $message = $repository->create($household, $sender, 'Test message');

        $this->assertTrue($message->household->is($household));
        $this->assertTrue($message->sender->is($sender));
        $this->assertSame('Test message', $message->content);
        $this->assertDatabaseHas('household_messages', [
            'id' => $message->getKey(),
            'household_id' => $household->getKey(),
            'sender_id' => $sender->getKey(),
            'content' => 'Test message',
        ]);
    }

    public function test_repository_paginates_only_household_messages_in_reverse_chronological_order(): void
    {
        $household = Household::factory()->create();
        $otherHousehold = Household::factory()->create();
        $sender = User::factory()->create();
        $oldest = HouseholdMessage::factory()->for($household)->for($sender, 'sender')->create();
        $newest = HouseholdMessage::factory()->for($household)->for($sender, 'sender')->create();
        $deleted = HouseholdMessage::factory()->for($household)->for($sender, 'sender')->create();
        $deleted->delete();
        $foreign = HouseholdMessage::factory()->for($otherHousehold)->create();
        $repository = app(MessageRepository::class);

        $paginator = $repository->paginateForHousehold($household, 2);
        $messages = collect($paginator->items());

        $this->assertCount(2, $messages);
        $this->assertSame($deleted->getKey(), $messages->first()->getKey());
        $this->assertSame($newest->getKey(), $messages->last()->getKey());
        $this->assertTrue($messages->every->relationLoaded('sender'));
        $this->assertFalse($messages->contains('id', $oldest->getKey()));
        $this->assertFalse($messages->contains('id', $foreign->getKey()));
        $this->assertTrue($messages->first()->trashed());
        $this->assertTrue($paginator->hasMorePages());
    }

    public function test_repository_finds_message_owned_by_household(): void
    {
        $household = Household::factory()->create();
        $message = HouseholdMessage::factory()->for($household)->create();
        $repository = app(MessageRepository::class);

        $found = $repository->findForHousehold($household, $message->uuid);

        $this->assertTrue($found->is($message));
    }

    public function test_repository_does_not_find_message_from_another_household(): void
    {
        $household = Household::factory()->create();
        $foreignMessage = HouseholdMessage::factory()->create();
        $repository = app(MessageRepository::class);

        $this->expectException(ModelNotFoundException::class);

        $repository->findForHousehold($household, $foreignMessage->uuid);
    }

    public function test_repository_updates_content_and_records_edit_time(): void
    {
        $message = HouseholdMessage::factory()->create(['content' => 'Before editing']);
        $repository = app(MessageRepository::class);

        $repository->updateContent($message, 'After editing');

        $message->refresh();
        $this->assertSame('After editing', $message->content);
        $this->assertNotNull($message->edited_at);
    }

    public function test_repository_soft_deletes_message(): void
    {
        $message = HouseholdMessage::factory()->create();
        $repository = app(MessageRepository::class);

        $repository->delete($message);

        $this->assertTrue($message->trashed());
        $this->assertSoftDeleted('household_messages', [
            'id' => $message->getKey(),
        ]);
        $this->assertNotNull(HouseholdMessage::withTrashed()->find($message->getKey()));
    }

    public function test_repository_returns_next_cursor_page_without_duplicates(): void
    {
        $household = Household::factory()->create();
        HouseholdMessage::factory()->count(5)->for($household)->create();
        $repository = app(MessageRepository::class);

        $firstPage = $repository->paginateForHousehold($household, 2);
        $firstPageIds = collect($firstPage->items())->pluck('id');

        request()->query->set('cursor', $firstPage->nextCursor()?->encode());
        $secondPage = $repository->paginateForHousehold($household, 2);
        request()->query->remove('cursor');

        $secondPageIds = collect($secondPage->items())->pluck('id');

        $this->assertCount(2, $secondPage->items());
        $this->assertTrue($firstPageIds->intersect($secondPageIds)->isEmpty());
    }

    public function test_repository_eager_loads_sender(): void
    {
        $household = Household::factory()->create();
        HouseholdMessage::factory()->for($household)->create();
        $repository = app(MessageRepository::class);

        $paginator = $repository->paginateForHousehold($household, 15);

        $this->assertTrue($paginator->items()[0]->relationLoaded('sender'));
    }
}
