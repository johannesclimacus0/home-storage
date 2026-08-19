<?php

namespace App\Models;

use Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read HouseholdProduct|null $householdProduct
 * @property-read StorageLocation|null $storageLocation
 *
 * @method static \Database\Factories\StockFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock query()
 *
 * @mixin \Eloquent
 */
#[Fillable(['household_product_id', 'storage_location_id', 'quantity'])]
class Stock extends Model
{
    /** @use HasFactory<StockFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:3',
        ];
    }

    public function householdProduct(): BelongsTo
    {
        return $this->belongsTo(HouseholdProduct::class);
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }
}
