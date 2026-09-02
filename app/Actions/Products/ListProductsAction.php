<?php

namespace App\Actions\Products;

use App\Contracts\Products\ProductRepository;
use App\Models\Product;
use App\Support\Cache\CatalogCache;
use Illuminate\Support\Collection;

final readonly class ListProductsAction
{
    public function __construct(
        private ProductRepository $productRepository,
        private CatalogCache $cache
    ) {}

    /**
     * @return Collection<int, array<string, mixed>>
     */
    public function handle(): Collection
    {
        return $this->cache->rememberProducts(
            fn (): Collection => $this->productRepository
                ->getAllOrdered()
                ->map(fn (Product $product): array => [
                    'uuid' => $product->uuid,
                    'name' => $product->name,
                    'measurement_type' => $product->measurement_type->value,
                ])
        );
    }
}
