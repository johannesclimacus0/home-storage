<?php

namespace App\Models;

use Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_product_id
 * @property int $storage_location_id
 * @property numeric $quantity
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\HouseholdProduct $householdProduct
 * @property-read \App\Models\StorageLocation $storageLocation
 * @method static \Database\Factories\StockFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereHouseholdProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereStorageLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[Fillable(['household_product_id', 'storage_location_id', 'quantity'])]
class Stock extends Model
{
    /** @use HasFactory<StockFactory> */
    use HasFactory;

    protected $casts = [
        'quantity' => 'decimal:3',
    ];

    public function householdProduct(): BelongsTo
    {
        return $this->belongsTo(HouseholdProduct::class);
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }
}
