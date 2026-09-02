<?php

namespace App\Providers;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\Contracts\Inventory\LowStockReminderRepository;
use App\Contracts\Inventory\StockMovementRepository;
use App\Contracts\Messages\MessageRepository;
use App\Contracts\Notes\RecipeNoteRepository;
use App\Contracts\Notifications\NotificationRepository;
use App\Contracts\Products\ProductRepository;
use App\Contracts\Recipes\RecipeAvailabilityRepository;
use App\Contracts\Recipes\RecipeRepository;
use App\Contracts\Shopping\ShoppingListRepository;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Observers\ProductCacheObserver;
use App\Observers\RecipeCacheObserver;
use App\Repositories\EloquentHouseholdMessageRepository;
use App\Repositories\EloquentHouseholdRepository;
use App\Repositories\EloquentInventoryRepository;
use App\Repositories\EloquentLowStockReminderRepository;
use App\Repositories\EloquentNotificationRepository;
use App\Repositories\EloquentProductRepository;
use App\Repositories\EloquentRecipeAvailabilityRepository;
use App\Repositories\EloquentRecipeNoteRepository;
use App\Repositories\EloquentRecipeRepository;
use App\Repositories\EloquentShoppingListRepository;
use App\Repositories\EloquentStockMovementRepository;
use App\Support\Cache\CatalogCache;
use App\Support\Cache\HouseholdCache;
use App\Support\Cache\RecipeCache;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
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
        $this->app->bind(RecipeAvailabilityRepository::class, EloquentRecipeAvailabilityRepository::class);
        $this->app->bind(RecipeNoteRepository::class, EloquentRecipeNoteRepository::class);

        $this->app->when([HouseholdCache::class, CatalogCache::class, RecipeCache::class])
            ->needs('$ttl')
            ->giveConfig('cache.domain_ttl', 300);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Product::observe(ProductCacheObserver::class);
        Recipe::observe(RecipeCacheObserver::class);
        RecipeIngredient::observe(RecipeCacheObserver::class);
        RecipeStep::observe(RecipeCacheObserver::class);

        RateLimiter::for('authenticated-api', function (Request $request) {
            return Limit::perMinute(300)
                ->by('user:' . $request->user()->getAuthIdentifier());
        });

        RateLimiter::for('write-operations', function (Request $request) {
            return Limit::perMinute(60)
                ->by('user:' . $request->user()->getAuthIdentifier());
        });

        RateLimiter::for('chat-messages', function (Request $request) {
            $userId = $request->user()->getAuthIdentifier();
            $householdUuid = $request->route()->originalParameter('household');

            return Limit::perMinute(30)
                ->by("user:{$userId}:household:{$householdUuid}");
        });
    }
}
