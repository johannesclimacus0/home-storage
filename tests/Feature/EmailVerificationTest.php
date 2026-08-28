<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\Auth\VerifyEmailNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Notification::fake();
    }

    public function test_user_can_verify_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->assertFalse($user->hasVerifiedEmail());

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => sha1($user->getEmailForVerification()),
            ]
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $user->refresh();
        $this->assertTrue($user->hasVerifiedEmail());
        $response->assertRedirect();
    }

    public function test_user_cannot_verify_email_with_invalid_signature(): void
    {
        $user = User::factory()->unverified()->create();

        $hash = sha1($user->getEmailForVerification());
        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            [
                'id' => $user->getKey(),
                'hash' => $hash,
            ]
        );

        $verificationUrl = str_replace(
            $hash,
            str_repeat('0', 40),
            $verificationUrl,
        );

        $response = $this->actingAs($user)->get($verificationUrl);

        $user->refresh();

        $response->assertForbidden();
        $this->assertFalse($user->hasVerifiedEmail());
    }

    public function test_unverified_user_can_request_another_verification_email(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user);

        $response = $this->postJson('/email/verification-notification');

        $response->assertAccepted();

        Notification::assertSentTo($user, VerifyEmailNotification::class);

        $user->refresh();

        $this->assertFalse($user->hasVerifiedEmail());
    }
}
