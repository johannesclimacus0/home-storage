<?php

namespace Tests\Feature\RateLimiting;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WriteOperationRateLimitTest extends TestCase
{
    use RefreshDatabase;

    public function test_write_operations_are_limited_per_user(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $this->freezeTime();
        $this->actingAs($user);

        for ($attempt = 1; $attempt <= 60; $attempt++) {
            $lastAllowedResponse = $this->patchJson('/api/notifications/read-all');
        }

        $lastAllowedResponse->assertOk();
        $this->patchJson('/api/notifications/read-all')->assertStatus(429);

        $this->actingAs($otherUser)
            ->patchJson('/api/notifications/read-all')
            ->assertOk();
    }
}
