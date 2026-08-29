<?php

namespace JohannesClimacus\ArtisanFactory\Tests\Support\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use JohannesClimacus\ArtisanFactory\Tests\Support\Factories\TestUserFactory;

final class TestUser extends Model
{
    /** @use HasFactory<TestUserFactory> */
    use HasFactory;

    protected $table = 'test_users';

    protected $guarded = [];

    protected $casts = [
        'email_verified_at' => 'immutable_datetime'
    ];

    protected static function newFactory(): TestUserFactory
    {
        return TestUserFactory::new();
    }
}
