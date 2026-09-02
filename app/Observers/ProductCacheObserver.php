<?php

namespace App\Observers;

use App\Models\Product;
use App\Support\Cache\CatalogCache;
use App\Support\Cache\RecipeCache;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

final readonly class ProductCacheObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(
        private CatalogCache $catalog,
        private RecipeCache $recipes
    ) {}

    public function created(Product $product): void
    {
        $this->forgetDependentCaches();
    }

    public function updated(Product $product): void
    {
        $this->forgetDependentCaches();
    }

    public function deleted(Product $product): void
    {
        $this->forgetDependentCaches();
    }

    private function forgetDependentCaches(): void
    {
        $this->catalog->forgetProducts();
        $this->recipes->forgetRecipes();
    }
}
