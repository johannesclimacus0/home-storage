<?php

namespace Tests\Feature\Messages;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMessage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdMessageControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_send_list_update_and_delete_own_message(): void
    {
        [$household, $member] = $this->householdWithMember();

        $response = $this->actingAs($member)
            ->postJson("/api/households/{$household->uuid}/messages", [
                'content' => '  Test message  ',
            ]);

        $response->assertCreated()
            ->assertJsonPath('data.content', 'Test message')
            ->assertJsonPath('data.sender.id', $member->getKey())
            ->assertJsonPath('data.is_mine', true)
            ->assertJsonPath('data.deleted_at', null);

        $messageUuid = $response->json('data.uuid');

        $this->actingAs($member)
            ->getJson("/api/households/{$household->uuid}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $messageUuid)
            ->assertJsonPath('data.0.content', 'Test message');

        $this->actingAs($member)
            ->patchJson("/api/households/{$household->uuid}/messages/{$messageUuid}", [
                'content' => 'Edited message',
            ])
            ->assertOk()
            ->assertJsonPath('data.content', 'Edited message')
            ->assertJsonPath('data.is_mine', true)
            ->assertJsonPath('data.edited_at', fn ($value) => is_string($value));

        $this->actingAs($member)
            ->deleteJson("/api/households/{$household->uuid}/messages/{$messageUuid}")
            ->assertNoContent();

        $this->assertSoftDeleted('household_messages', ['uuid' => $messageUuid]);

        $this->actingAs($member)
            ->getJson("/api/households/{$household->uuid}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $messageUuid)
            ->assertJsonPath('data.0.content', null)
            ->assertJsonPath('data.0.deleted_at', fn ($value) => is_string($value));
    }

    public function test_messages_are_isolated_by_household(): void
    {
        [$household, $member] = $this->householdWithMember();
        $ownMessage = HouseholdMessage::factory()->for($household)->create();
        $foreignMessage = HouseholdMessage::factory()->create();

        $this->actingAs($member)
            ->getJson("/api/households/{$household->uuid}/messages")
            ->assertOk()
            ->assertJsonPath('data.0.uuid', $ownMessage->uuid)
            ->assertJsonMissing(['uuid' => $foreignMessage->uuid]);
    }

    public function test_member_cannot_update_or_delete_another_members_message(): void
    {
        [$household, $member] = $this->householdWithMember();
        $author = User::factory()->create();
        $household->householdMemberships()->create([
            'user_id' => $author->getKey(),
            'role' => HouseholdRole::Member,
        ]);
        $message = HouseholdMessage::factory()
            ->for($household)
            ->for($author, 'sender')
            ->create(['content' => 'Original message']);

        $this->actingAs($member)
            ->patchJson("/api/households/{$household->uuid}/messages/{$message->uuid}", [
                'content' => 'Changed by another member',
            ])
            ->assertForbidden();

        $this->actingAs($member)
            ->deleteJson("/api/households/{$household->uuid}/messages/{$message->uuid}")
            ->assertForbidden();

        $this->assertSame('Original message', $message->refresh()->content);
        $this->assertNull($message->deleted_at);
    }

    public function test_outsider_cannot_access_household_chat(): void
    {
        $household = Household::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/households/{$household->uuid}/messages")
            ->assertForbidden();

        $this->actingAs($outsider)
            ->postJson("/api/households/{$household->uuid}/messages", [
                'content' => 'Forbidden message',
            ])
            ->assertForbidden();

        $this->assertDatabaseCount('household_messages', 0);
    }

    public function test_message_content_is_required_and_limited(): void
    {
        [$household, $member] = $this->householdWithMember();

        $this->actingAs($member)
            ->postJson("/api/households/{$household->uuid}/messages", ['content' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');

        $this->actingAs($member)
            ->postJson("/api/households/{$household->uuid}/messages", [
                'content' => str_repeat('a', 2001),
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');
    }

    private function householdWithMember(): array
    {
        $household = Household::factory()->create();
        $member = User::factory()->create();
        $household->householdMemberships()->create([
            'user_id' => $member->getKey(),
            'role' => HouseholdRole::Member,
        ]);

        return [$household, $member];
    }
}
