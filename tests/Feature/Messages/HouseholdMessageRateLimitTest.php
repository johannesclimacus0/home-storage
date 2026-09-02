<?php

namespace Tests\Feature\Messages;

use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdMessageRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_cannot_send_more_than_thirty_messages_per_minute_to_same_household(): void
    {
        $user = User::factory()->create();
        $household = Household::factory()->create();
        HouseholdMembership::factory()->for($household)->for($user)->create();
        $this->freezeTime();
        $this->actingAs($user);

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->postJson("/api/households/{$household->uuid}/messages", [
                'content' => "Allowed test message or smth",
            ])->assertCreated();
        }

        $this->postJson("/api/households/{$household->uuid}/messages", [
            'content' => 'Blocked test message',
        ])->assertStatus(429);

        $this->assertDatabaseCount('household_messages', 30);
    }

    public function test_message_rate_limit_is_scoped_by_user_and_household(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();
        $householdA = Household::factory()->create();
        $householdB = Household::factory()->create();
        HouseholdMembership::factory()->for($householdA)->for($userA)->create();
        HouseholdMembership::factory()->for($householdB)->for($userA)->create();
        HouseholdMembership::factory()->for($householdA)->for($userB)->create();
        $this->freezeTime();

        for ($attempt = 1; $attempt <= 30; $attempt++) {
            $this->actingAs($userA)
                ->postJson("/api/households/{$householdA->uuid}/messages", [
                    'content' => "Allowed test message or smth",
                ])->assertCreated();
        }

        $this->actingAs($userA)
            ->postJson("/api/households/{$householdA->uuid}/messages", [
                'content' => 'Blocked test message',
            ])->assertStatus(429);
        $this->actingAs($userA)
            ->postJson("/api/households/{$householdB->uuid}/messages", [
                'content' => 'Allowed in another household or smth',
            ])->assertCreated();
        $this->actingAs($userB)
            ->postJson("/api/households/{$householdA->uuid}/messages", [
                'content' => 'Allowed for another user',
            ])->assertCreated();
        $this->assertDatabaseCount('household_messages', 32);
    }
}
