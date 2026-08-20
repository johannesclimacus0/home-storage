<?php

namespace App\Models;

use Database\Factories\HouseholdProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read Household|null $household
 * @property-read Product|null $product
 * @property-read Collection<int, Stock> $stocks
 * @property-read int|null $stocks_count
 *
 * @method static \Database\Factories\HouseholdProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct query()
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'household_id',
    'product_id',
    'low_stock_threshold',
    'low_stock_since',
])]
class HouseholdProduct extends Model
{
    /** @use HasFactory<HouseholdProductFactory> */
    use HasFactory;

    protected $casts = [
        'low_stock_threshold' => 'decimal:3',
        'low_stock_since' => 'immutable_datetime',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stocks(): HasMany
    {
        return $this->hasMany(Stock::class);
    }

    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }
}
