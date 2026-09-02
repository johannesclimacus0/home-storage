<?php

namespace App\Support\Cache;

use Closure;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Support\Collection;

final readonly class CatalogCache
{
    private const string PRODUCTS_KEY = 'catalog:products';

    public function __construct(
        private Repository $cache,
        private int $ttl = 300
    ) {}

    /**
     * @param  Closure(): Collection<int, array<string, mixed>>  $resolver
     * @return Collection<int, array<string, mixed>>
     */
    public function rememberProducts(Closure $resolver): Collection
    {
        $products = $this->cache->remember(
            self::PRODUCTS_KEY,
            $this->ttl,
            fn (): array => $resolver()->all()
        );

        return collect($products);
    }

    public function forgetProducts(): void
    {
        $this->cache->forget(self::PRODUCTS_KEY);
    }
}
