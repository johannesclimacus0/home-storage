<?php

namespace Tests\Unit\Support\Cache;

use App\Support\Cache\CatalogCache;
use Carbon\CarbonImmutable;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;
use PHPUnit\Framework\TestCase;

final class CatalogCacheTest extends TestCase
{
    public function test_it_remembers_catalog_until_configured_ttl_expires(): void
    {
        CarbonImmutable::setTestNow('2026-09-02 10:00:00');
        $cache = new CatalogCache(new Repository(new ArrayStore), 1);
        $calls = 0;
        $resolver = function () use (&$calls): Collection {
            $calls++;

            return collect([['name' => 'Test product']]);
        };

        $cache->rememberProducts($resolver);
        $cache->rememberProducts($resolver);
        CarbonImmutable::setTestNow('2026-09-02 10:00:02');
        $cache->rememberProducts($resolver);

        $this->assertSame(2, $calls);

        CarbonImmutable::setTestNow();
    }

    public function test_it_forgets_products(): void
    {
        $cache = new CatalogCache(new Repository(new ArrayStore));
        $calls = 0;
        $resolver = function () use (&$calls): Collection {
            $calls++;

            return collect();
        };

        $cache->rememberProducts($resolver);
        $cache->forgetProducts();
        $cache->rememberProducts($resolver);

        $this->assertSame(2, $calls);
    }
}
