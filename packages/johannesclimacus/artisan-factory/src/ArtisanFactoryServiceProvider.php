<?php

namespace JohannesClimacus\ArtisanFactory;

use Illuminate\Support\ServiceProvider;
use JohannesClimacus\ArtisanFactory\Commands\FactoryCreateCommand;

class ArtisanFactoryServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/factory-create.php',
            'factory-create'
        );
    }

    public function boot(): void
    {
        if (!$this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            FactoryCreateCommand::class
        ]);

        $this->publishes([
            __DIR__.'/../config/factory-create.php'
            => config_path('factory-create.php')
        ], 'factory-create-config');
    }
}
