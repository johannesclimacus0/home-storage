<?php

namespace App\Observers;

use App\Support\Cache\RecipeCache;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;
use Illuminate\Database\Eloquent\Model;

final readonly class RecipeCacheObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private RecipeCache $cache) {}

    public function created(Model $model): void
    {
        $this->cache->forgetRecipes();
    }

    public function updated(Model $model): void
    {
        $this->cache->forgetRecipes();
    }

    public function deleted(Model $model): void
    {
        $this->cache->forgetRecipes();
    }
}
