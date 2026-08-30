<?php

namespace App\Providers;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\Contracts\Inventory\LowStockReminderRepository;
use App\Contracts\Inventory\StockMovementRepository;
use App\Contracts\Messages\MessageRepository;
use App\Contracts\Notifications\NotificationRepository;
use App\Contracts\Products\ProductRepository;
use App\Contracts\Recipes\RecipeRepository;
use App\Contracts\Shopping\ShoppingListRepository;
use App\Repositories\EloquentHouseholdMessageRepository;
use App\Repositories\EloquentHouseholdRepository;
use App\Repositories\EloquentInventoryRepository;
use App\Repositories\EloquentLowStockReminderRepository;
use App\Repositories\EloquentNotificationRepository;
use App\Repositories\EloquentProductRepository;
use App\Repositories\EloquentRecipeRepository;
use App\Repositories\EloquentShoppingListRepository;
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
        $this->app->bind(LowStockReminderRepository::class, EloquentLowStockReminderRepository::class);
        $this->app->bind(ProductRepository::class, EloquentProductRepository::class);
        $this->app->bind(StockMovementRepository::class, EloquentStockMovementRepository::class);
        $this->app->bind(NotificationRepository::class, EloquentNotificationRepository::class);
        $this->app->bind(ShoppingListRepository::class, EloquentShoppingListRepository::class);
        $this->app->bind(MessageRepository::class, EloquentHouseholdMessageRepository::class);
        $this->app->bind(RecipeRepository::class, EloquentRecipeRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
