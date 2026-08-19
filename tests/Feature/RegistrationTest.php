<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/register', [
            'name' => 'Test Name',
            'email' => 'test@example.org',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertCreated();

        $user = User::query()
            ->where('email', 'test@example.org')
            ->firstOrFail();

        $this->assertDatabaseHas('users', [
            'email' => 'test@example.org',
            'name' => 'Test Name',
        ]);
        $this->assertNull($user->email_verified_at);
        $this->assertAuthenticatedAs($user);
        $this->assertTrue(Hash::check('password', $user->password));
        Notification::assertSentTo([$user], VerifyEmail::class);
    }

    public function test_user_cannot_register_with_existing_email(): void
    {
        $existingUser = User::factory()->create();

        $response = $this->postJson('/register', [
            'name' => 'Test Name',
            'email' => $existingUser->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors('email');
        $this->assertDatabaseCount('users', 1);
        $this->assertGuest();
        Notification::assertNothingSent();
    }
}
