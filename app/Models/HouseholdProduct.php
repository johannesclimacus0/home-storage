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
 * @property int $id
 * @property int $household_id
 * @property int $product_id
 * @property numeric $low_stock_threshold
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Carbon\CarbonImmutable|null $low_stock_since
 * @property-read \App\Models\Household $household
 * @property-read Collection<int, \App\Models\LowStockNotificationState> $lowStockNotificationStates
 * @property-read int|null $low_stock_notification_states_count
 * @property-read \App\Models\Product $product
 * @property-read Collection<int, \App\Models\StockMovement> $stockMovements
 * @property-read int|null $stock_movements_count
 * @property-read Collection<int, \App\Models\Stock> $stocks
 * @property-read int|null $stocks_count
 * @method static \Database\Factories\HouseholdProductFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct whereHouseholdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct whereLowStockSince($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct whereLowStockThreshold($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdProduct whereUpdatedAt($value)
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

    public function lowStockNotificationStates(): HasMany
    {
        return $this->hasMany(LowStockNotificationState::class);
    }
}
