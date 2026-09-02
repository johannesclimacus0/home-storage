<?php

namespace App\Support\Cache;

use App\Enums\RecipeAvailabilityFilter;
use Closure;
use Illuminate\Cache\Repository;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class RecipeCache
{
    private const string RECIPES_TAG = 'recipes';

    public function __construct(
        private Repository $cache,
        private int $ttl = 300
    ) {}

    /**
     * @param  Closure(): LengthAwarePaginator<int, array<string, mixed>>  $resolver
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function rememberList(int $perPage, int $page, Closure $resolver): LengthAwarePaginator
    {
        return $this->rememberPaginator(
            $this->cache->tags([self::RECIPES_TAG]),
            "page:{$page}:per-page:{$perPage}",
            $resolver
        );
    }

    /**
     * @param  Closure(): LengthAwarePaginator<int, array<string, mixed>>  $resolver
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function rememberHouseholdList(
        string $householdUuid,
        RecipeAvailabilityFilter $filter,
        int $perPage,
        int $page,
        Closure $resolver
    ): LengthAwarePaginator {
        $key = 'households:' . $householdUuid
            . ':filter:' . $filter->value
            . ":page:{$page}:per-page:{$perPage}";

        return $this->rememberPaginator(
            $this->cache->tags([
                self::RECIPES_TAG,
                HouseholdCache::inventoryTag($householdUuid),
            ]),
            $key,
            $resolver
        );
    }

    /**
     * @param  Closure(): array<string, mixed>  $resolver
     * @return array<string, mixed>
     */
    public function rememberAvailability(
        string $householdUuid,
        string $recipeUuid,
        Closure $resolver
    ): array {
        $key = 'households:' . $householdUuid
            . ':recipe:' . $recipeUuid
            . ':availability';

        return $this->cache
            ->tags([
                self::RECIPES_TAG,
                HouseholdCache::inventoryTag($householdUuid),
            ])
            ->remember($key, $this->ttl, $resolver);
    }

    public function forgetRecipes(): void
    {
        $this->cache->tags([self::RECIPES_TAG])->flush();
    }

    /**
     * @param  Closure(): LengthAwarePaginator<int, array<string, mixed>>  $resolver
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    private function rememberPaginator(
        Repository $cache,
        string $key,
        Closure $resolver
    ): LengthAwarePaginator {
        $payload = $cache->remember($key, $this->ttl, function () use ($resolver): array {
            $paginator = $resolver();

            return [
                'items' => $paginator->items(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'path' => $paginator->path(),
                'page_name' => $paginator->getPageName(),
            ];
        });

        return new LengthAwarePaginator(
            items: $payload['items'],
            total: $payload['total'],
            perPage: $payload['per_page'],
            currentPage: $payload['current_page'],
            options: [
                'path' => $payload['path'],
                'pageName' => $payload['page_name'],
            ]
        );
    }
}
