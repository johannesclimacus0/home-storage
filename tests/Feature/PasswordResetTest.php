<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_user_can_request_password_reset_link(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_user_can_reset_password_with_valid_token(): void
    {
        $oldPassword = 'old-password';
        $newPassword = 'new-password';

        $user = User::factory()->create(['password' => $oldPassword]);

        $token = Password::createToken($user);

        $response = $this->postJson('/reset-password', [
            'email' => $user->email,
            'token' => $token,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ]);

        $response->assertOk();

        $user->refresh();

        $this->assertTrue(Hash::check($newPassword, $user->password));
        $this->assertFalse(Hash::check($oldPassword, $user->password));
    }
}
