<?php

namespace JohannesClimacus\ArtisanFactory\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use JohannesClimacus\ArtisanFactory\ArtisanFactoryServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ArtisanFactoryServiceProvider::class
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');

        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => ''
        ]);

        $app['config']->set(
            'factory-create.model_namespace',
            'JohannesClimacus\\ArtisanFactory\\Tests\\Support\\Models'
        );
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('test_users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->timestamps();
        });
    }
}
