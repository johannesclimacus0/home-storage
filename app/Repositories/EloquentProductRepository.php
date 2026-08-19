<?php

namespace App\Repositories;

use App\Contracts\Products\ProductRepository;
use App\Enums\MeasurementType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

final class EloquentProductRepository implements ProductRepository
{
    /**
     * @return Collection<int, Product>
     */
    public function getAllOrdered(): Collection
    {
        return Product::query()
            ->orderBy('name')
            ->orderBy('id')
            ->get();
    }

    public function findByUuid(string $uuid): Product
    {
        return Product::query()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function create(string $name, MeasurementType $measurementType): Product
    {
        return Product::query()->create([
            'name' => $name,
            'measurement_type' => $measurementType,
        ]);
    }
}
