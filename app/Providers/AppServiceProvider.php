<?php

namespace App\Providers;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\Contracts\Inventory\StockMovementRepository;
use App\Contracts\Products\ProductRepository;
use App\Repositories\EloquentHouseholdRepository;
use App\Repositories\EloquentInventoryRepository;
use App\Repositories\EloquentProductRepository;
use App\Repositories\EloquentStockMovementRepository;
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
        $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
        $this->app->bind(StockMovementRepository::class, EloquentStockMovementRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
