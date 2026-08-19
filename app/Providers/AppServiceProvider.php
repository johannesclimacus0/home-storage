<?php

namespace App\Providers;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\Repositories\EloquentHouseholdRepository;
use App\Repositories\EloquentInventoryRepository;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(HouseholdRepository::class, EloquentHouseholdRepository::class);
        $this->app->bind(InventoryRepository::class, EloquentInventoryRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
