<?php

namespace App\Support\Cache;

use Closure;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;

final readonly class HouseholdCache
{
    public function __construct(
        private Repository $cache,
        private int $ttl = 300
    ) {}

    /**
     * @param  Closure(): Collection<int, array<string, mixed>>  $resolver
     * @return Collection<int, array<string, mixed>>
     */
    public function rememberInventory(string $uuid, Closure $resolver): Collection
    {
        $inventory = $this->cache->tags([self::inventoryTag($uuid)])->remember(
            'inventory',
            $this->ttl,
            fn (): array => $resolver()->all()
        );

        return collect($inventory);
    }

    public function forgetInventory(string $uuid): void
    {
        $this->cache->tags([self::inventoryTag($uuid)])->flush();
    }

    public static function inventoryTag(string $uuid): string
    {
        return 'households:' . $uuid . ':inventory';
    }
}
