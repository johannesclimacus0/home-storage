<?php

namespace JohannesClimacus\ArtisanFactory\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use JohannesClimacus\ArtisanFactory\Tests\Support\Models\TestUser;
use JohannesClimacus\ArtisanFactory\Tests\TestCase;

class FactoryCreateCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_requested_number_of_models(): void
    {
        $this->artisan('factory:create', [
            'model' => 'TestUser',
            '--count' => 3
        ])
            ->expectsOutput('Created 3 TestUser records.')
            ->assertSuccessful();

        $this->assertSame(3, TestUser::query()->count());
    }

    public function test_command_uses_default_count(): void
    {
        $this->artisan('factory:create', [
            'model' => 'TestUser'
        ])
            ->expectsOutput('Created 1 TestUser record.')
            ->assertSuccessful();

        $this->assertSame(1, TestUser::query()->count());
    }

    public function test_command_applies_factory_state(): void
    {
        $this->artisan('factory:create', [
            'model' => 'TestUser',
            '--state' => ['unverified']
        ])->assertSuccessful();

        $this->assertNull(TestUser::query()->sole()->email_verified_at);
    }

    public function test_command_applies_attributes(): void
    {
        $this->artisan('factory:create', [
            'model' => 'TestUser',
            '--set' => [
                'name=a=b',
                'email=test@example.org',
                'email_verified_at=null'
            ]
        ])->assertSuccessful();

        $user = TestUser::query()->sole();

        $this->assertSame('a=b', $user->name);
        $this->assertSame('test@example.org', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_command_rejects_count_below_one(): void
    {
        $this->artisan('factory:create', [
            'model' => 'TestUser',
            '--count' => 0
        ])
            ->expectsOutput('Count must be between 1 and 20.')
            ->assertFailed();

        $this->assertSame(0, TestUser::query()->count());
    }

    public function test_command_rejects_count_above_configured_limit(): void
    {
        config(['factory-create.max_count' => 2]);

        $this->artisan('factory:create', [
            'model' => 'TestUser',
            '--count' => 3
        ])
            ->expectsOutput('Count must be between 1 and 2.')
            ->assertFailed();

        $this->assertSame(0, TestUser::query()->count());
    }

    public function test_command_rejects_unknown_model(): void
    {
        $this->artisan('factory:create', [
            'model' => 'UnknownModel'
        ])
            ->expectsOutput("Model 'UnknownModel' does not exist.")
            ->assertFailed();
    }

    public function test_command_rejects_unknown_factory_state(): void
    {
        $this->artisan('factory:create', [
            'model' => 'TestUser',
            '--state' => ['unknown']
        ])
            ->expectsOutput("Factory state 'unknown' is not found for model 'TestUser'.")
            ->assertFailed();

        $this->assertSame(0, TestUser::query()->count());
    }

    public function test_command_rejects_inherited_factory_method_as_state(): void
    {
        $this->artisan('factory:create', [
            'model' => 'TestUser',
            '--state' => ['create']
        ])
            ->expectsOutput("Factory state 'create' is not found for model 'TestUser'.")
            ->assertFailed();

        $this->assertSame(0, TestUser::query()->count());
    }

    public function test_command_rejects_invalid_attribute_format(): void
    {
        $this->artisan('factory:create', [
            'model' => 'TestUser',
            '--set' => ['invalid-attribute']
        ])
            ->expectsOutput("Attribute 'invalid-attribute' must be of a key=value format.")
            ->assertFailed();

        $this->assertSame(0, TestUser::query()->count());
    }

    public function test_command_rejects_duplicate_attributes(): void
    {
        $this->artisan('factory:create', [
            'model' => 'TestUser',
            '--set' => [
                'name=First name',
                'name=Second name'
            ]
        ])
            ->expectsOutput("Attribute 'name' was provided more than once.")
            ->assertFailed();

        $this->assertSame(0, TestUser::query()->count());
    }
}
