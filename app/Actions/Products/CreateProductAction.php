<?php

namespace App\Actions\Products;

use App\Contracts\Products\ProductRepository;
use App\DTO\Products\CreateProductData;
use App\Exceptions\Products\ProductConflict;
use App\Models\Product;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

final readonly class CreateProductAction
{
    public function __construct(private ProductRepository $products) {}

    public function handle(CreateProductData $data): Product
    {
        try {
            return DB::transaction(
                fn (): Product => $this->products->create(
                    name: Str::squish($data->name),
                    measurementType: $data->measurementType,
                ),
            );
        } catch (UniqueConstraintViolationException) {
            throw new ProductConflict(__('messages.products.duplicate'));
        }
    }
}
