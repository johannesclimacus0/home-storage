<?php

namespace App\Actions\Products;

use App\Contracts\Products\ProductRepository;
use App\Models\Product;

final readonly class ShowProductAction
{
    public function __construct(private ProductRepository $productRepository) {}

    public function handle(string $uuid): Product
    {
        return $this->productRepository->findByUuid($uuid);
    }
}
