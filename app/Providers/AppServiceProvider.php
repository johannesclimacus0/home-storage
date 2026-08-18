<?php

namespace App\Providers;

use App\Contracts\Households\HouseholdRepository;
use App\Repositories\EloquentHouseholdRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HouseholdRepository::class, EloquentHouseholdRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
