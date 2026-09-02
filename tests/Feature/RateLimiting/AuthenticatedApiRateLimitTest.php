<?php

namespace Tests\Feature\RateLimiting;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedApiRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_api_is_limited_per_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->freezeTime();
        $this->actingAs($user);

        for ($attempt = 1; $attempt <= 300; $attempt++) {
            $lastAllowedResponse = $this->getJson('/api/user');
        }

        $lastAllowedResponse->assertOk();
        $this->getJson('/api/user')->assertStatus(429);

        $this->actingAs($otherUser)
            ->getJson('/api/user')
            ->assertOk();
    }
}
