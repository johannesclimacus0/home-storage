<?php

namespace App\Models;

use App\Enums\MeasurementType;
use App\Traits\HasUuidRouteKey;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property MeasurementType $measurement_type
 * @property-read Collection<int, HouseholdProduct> $householdProducts
 * @property-read int|null $household_products_count
 *
 * @method static \Database\Factories\ProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Product query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['name', 'measurement_type'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUuidRouteKey;

    protected $casts = [
        'measurement_type' => MeasurementType::class,
    ];

    public function householdProducts(): HasMany
    {
        return $this->hasMany(HouseholdProduct::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
