<?php

namespace Tests\Unit\Support\Cache;

use App\Support\Cache\HouseholdCache;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

class HouseholdCacheTest extends TestCase
{
    public function test_it_remembers_household_inventory(): void
    {
        $store = new ArrayStore;
        $repository = new Repository($store);
        $cache = new HouseholdCache($repository);

        $calls = 0;
        $uuid = 'test-household-uuid';

        $closure = function () use (&$calls): Collection {
            $calls++;

            return collect([
                [
                    'uuid' => 'test-product-uuid',
                    'name' => 'Test product',
                    'quantity' => '2.000',
                ],
            ]);
        };

        $firstResult = $cache->rememberInventory($uuid, $closure);
        $secondResult = $cache->rememberInventory($uuid, $closure);

        $this->assertSame(1, $calls);
        $this->assertSame($firstResult->all(), $secondResult->all());
        $this->assertInstanceOf(Collection::class, $firstResult);
    }

    public function test_different_households_use_different_cache_entries(): void
    {
        $store = new ArrayStore;
        $repository = new Repository($store);
        $cache = new HouseholdCache($repository);

        $firstCalls = 0;
        $secondCalls = 0;

        $firstResolver = function () use (&$firstCalls): Collection {
            $firstCalls++;

            return collect([
                [
                    'uuid' => 'test-first-product-uuid',
                    'name' => 'Mlik',
                    'quantity' => '2.000',
                ],
            ]);
        };

        $secondResolver = function () use (&$secondCalls): Collection {
            $secondCalls++;

            return collect([
                [
                    'uuid' => 'test-second-product-uuid',
                    'name' => 'Bread',
                    'quantity' => '2.000',
                ],
            ]);
        };

        $firstResult = $cache->rememberInventory('test-first-household-uuid', $firstResolver);
        $secondResult = $cache->rememberInventory('test-second-household-uuid', $secondResolver);

        $this->assertSame(1, $firstCalls);
        $this->assertSame(1, $secondCalls);
        $this->assertSame('Mlik', $firstResult->first()['name']);
        $this->assertSame('Bread', $secondResult->first()['name']);
    }

    public function test_it_forgets_household_inventory(): void
    {
        $store = new ArrayStore;
        $repository = new Repository($store);
        $cache = new HouseholdCache($repository);

        $calls = 0;
        $uuid = 'test-household-uuid';

        $resolver = function () use (&$calls): Collection {
            $calls++;

            return collect([
                [
                    'uuid' => 'test-product-uuid',
                    'name' => 'Milk',
                    'quantity' => '2.000',
                ],
            ]);
        };

        $cache->rememberInventory($uuid, $resolver);
        $cache->forgetInventory($uuid);
        $result = $cache->rememberInventory($uuid, $resolver);

        $this->assertSame(2, $calls);
        $this->assertSame('Milk', $result->first()['name']);
    }
}
