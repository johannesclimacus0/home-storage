<?php

namespace JohannesClimacus\ArtisanFactory\Tests\Support\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use JohannesClimacus\ArtisanFactory\Tests\Support\Models\TestUser;

/** @extends Factory<TestUser> */
final class TestUserFactory extends Factory
{
    protected $model = TestUser::class;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now()
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (): array => [
            'email_verified_at' => null
        ]);
    }
}
