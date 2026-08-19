<?php

namespace App\Contracts\Products;

use App\Enums\MeasurementType;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;

interface ProductRepository
{
    /**
     * @return Collection<int, Product>
     */
    public function getAllOrdered(): Collection;

    public function findByUuid(string $uuid): Product;

    public function create(string $name, MeasurementType $measurementType): Product;
}
