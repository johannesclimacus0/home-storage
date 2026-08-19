<?php

namespace App\Models;

use Database\Factories\HouseholdProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property-read \App\Models\Household|null $household
 * @property-read \App\Models\Product|null $product
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Stock> $stocks
 * @property-read int|null $stocks_count
 * @method static \Database\Factories\HouseholdProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct query()
 * @mixin \Eloquent
 */
#[Fillable(['household_id', 'product_id', 'low_stock_threshold'])]
class HouseholdProduct extends Model
{
    /** @use HasFactory<HouseholdProductFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'low_stock_threshold' => 'decimal:3',
        ];
    }

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
}
