<?php

namespace App\Actions\Products;

use App\Contracts\Products\ProductRepository;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListProductsAction
{
    public function __construct(private ProductRepository $productRepository) {}

    /**
     * @return Collection<int, Product>
     */
    public function handle(): Collection
    {
        return $this->productRepository->getAllOrdered();
    }
}
