<?php

namespace Tests\Feature\Inventory;

use App\Models\Household;
use App\Models\HouseholdProduct;
use App\Support\Cache\HouseholdCache;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class HouseholdProductObserverTest extends TestCase
{
    use DatabaseMigrations;

    public function test_creating_household_product_invalidates_inventory_cache(): void
    {
        $household = Household::factory()->create();
        $cache = $this->app->make(HouseholdCache::class);
        $calls = 0;
        $resolver = function () use (&$calls): Collection {
            $calls++;

            return collect();
        };

        $cache->rememberInventory($household->uuid, $resolver);

        HouseholdProduct::factory()->for($household)->create();

        $cache->rememberInventory($household->uuid, $resolver);

        $this->assertSame(2, $calls);
    }

    public function test_updating_household_product_invalidates_inventory_cache(): void
    {
        $household = Household::factory()->create();
        $householdProduct = HouseholdProduct::factory()->for($household)->create();
        $cache = $this->app->make(HouseholdCache::class);
        $calls = 0;
        $resolver = function () use (&$calls): Collection {
            $calls++;

            return collect();
        };

        $cache->rememberInventory($household->uuid, $resolver);

        $householdProduct->update(['low_stock_threshold' => '5.000']);

        $cache->rememberInventory($household->uuid, $resolver);

        $this->assertSame(2, $calls);
    }

    public function test_deleting_household_product_invalidates_inventory_cache(): void
    {
        $household = Household::factory()->create();
        $householdProduct = HouseholdProduct::factory()->for($household)->create();
        $cache = $this->app->make(HouseholdCache::class);
        $calls = 0;
        $resolver = function () use (&$calls): Collection {
            $calls++;

            return collect();
        };

        $cache->rememberInventory($household->uuid, $resolver);

        $householdProduct->deleteOrFail();

        $cache->rememberInventory($household->uuid, $resolver);

        $this->assertSame(2, $calls);
    }
}
