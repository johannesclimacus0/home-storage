<?php

namespace App\Models;

use App\Traits\HasUuidRouteKey;
use Database\Factories\StorageLocationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $household_id
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Household $household
 * @property-read Collection<int, StockMovement> $stockMovements
 * @property-read int|null $stock_movements_count
 * @property-read Collection<int, Stock> $stocks
 * @property-read int|null $stocks_count
 *
 * @method static \Database\Factories\StorageLocationFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageLocation newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageLocation newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageLocation query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageLocation whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageLocation whereHouseholdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageLocation whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageLocation whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageLocation whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StorageLocation whereUuid($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['household_id', 'name'])]
class StorageLocation extends Model
{
    /** @use HasFactory<StorageLocationFactory> */
    use HasFactory, HasUuidRouteKey;

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
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
