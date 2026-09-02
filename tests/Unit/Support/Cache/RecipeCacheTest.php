<?php

namespace Tests\Unit\Support\Cache;

use App\Enums\RecipeAvailabilityFilter;
use App\Support\Cache\HouseholdCache;
use App\Support\Cache\RecipeCache;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\TestCase;

final class RecipeCacheTest extends TestCase
{
    public function test_recipe_tag_invalidates_recipe_lists(): void
    {
        $repository = new Repository(new ArrayStore);
        $cache = new RecipeCache($repository);
        $calls = 0;
        $resolver = function () use (&$calls): LengthAwarePaginator {
            $calls++;

            return new LengthAwarePaginator(
                [['title' => 'Test recipe']],
                1,
                8,
                1
            );
        };

        $first = $cache->rememberList(8, 1, $resolver);
        $second = $cache->rememberList(8, 1, $resolver);
        $cache->forgetRecipes();
        $third = $cache->rememberList(8, 1, $resolver);

        $this->assertSame(2, $calls);
        $this->assertSame($first->items(), $second->items());
        $this->assertSame($first->items(), $third->items());
    }

    public function test_inventory_tag_invalidates_household_recipe_cache(): void
    {
        $repository = new Repository(new ArrayStore);
        $households = new HouseholdCache($repository);
        $cache = new RecipeCache($repository);
        $calls = 0;
        $resolver = function () use (&$calls): LengthAwarePaginator {
            $calls++;

            return new LengthAwarePaginator([], 0, 12, 1);
        };

        $cache->rememberHouseholdList(
            'test-household',
            RecipeAvailabilityFilter::All,
            12,
            1,
            $resolver
        );
        $cache->rememberHouseholdList(
            'test-household',
            RecipeAvailabilityFilter::All,
            12,
            1,
            $resolver
        );
        $households->forgetInventory('test-household');
        $cache->rememberHouseholdList(
            'test-household',
            RecipeAvailabilityFilter::All,
            12,
            1,
            $resolver
        );

        $this->assertSame(2, $calls);
    }
}
