<?php

namespace Tests\Feature\Broadcasting;

use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Broadcast;
use Tests\TestCase;

class HouseholdChannelAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'broadcasting.default' => 'reverb',
            'broadcasting.connections.reverb.key' => 'test-key',
            'broadcasting.connections.reverb.secret' => 'test-secret',
            'broadcasting.connections.reverb.app_id' => 'test-app-id'
        ]);

        Broadcast::purge('reverb');
        Broadcast::setDefaultDriver('reverb');

        require base_path('routes/channels.php');
    }

    public function test_household_member_can_authorize_private_household_channel(): void
    {
        $household = Household::factory()->create();
        $member = User::factory()->create();

        HouseholdMembership::factory()
            ->for($household)
            ->for($member, 'user')
            ->create();

        $response = $this->actingAs($member)->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-households.'.$household->uuid
        ]);

        $response->assertOk();
        $response->assertJsonStructure(['auth']);
    }

    public function test_outsider_cannot_authorize_private_household_channel(): void
    {
        $household = Household::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->postJson('/broadcasting/auth', [
                'socket_id' => '1234.5678',
                'channel_name' => 'private-households.'.$household->uuid
            ])
            ->assertForbidden();
    }

    public function test_guest_cannot_authorize_private_household_channel(): void
    {
        $household = Household::factory()->create();

        $this->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-households.'.$household->uuid
        ])->assertForbidden();
    }
}
