<?php

namespace App\Models;

use App\Enums\MeasurementUnit;
use App\Enums\StockMovementType;
use App\Traits\HasUuidRouteKey;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $household_id
 * @property int|null $household_product_id
 * @property int $product_id
 * @property int|null $storage_location_id
 * @property int|null $actor_user_id
 * @property StockMovementType $type
 * @property numeric $input_quantity
 * @property MeasurementUnit $input_unit
 * @property numeric $quantity_delta
 * @property numeric $quantity_before
 * @property numeric $quantity_after
 * @property string $product_name
 * @property string $storage_location_name
 * @property string $actor_name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $actor
 * @property-read Household $household
 * @property-read HouseholdProduct|null $householdProduct
 * @property-read Product $product
 * @property-read StorageLocation|null $storageLocation
 *
 * @method static \Database\Factories\StockMovementFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereActorName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereActorUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereHouseholdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereHouseholdProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereInputQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereInputUnit($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereProductName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereQuantityAfter($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereQuantityBefore($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereQuantityDelta($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereStorageLocationId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereStorageLocationName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockMovement whereUuid($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'household_id',
    'household_product_id',
    'product_id',
    'storage_location_id',
    'actor_user_id',
    'type',
    'input_unit',
    'input_quantity',
    'quantity_delta',
    'quantity_before',
    'quantity_after',
    'product_name',
    'storage_location_name',
    'actor_name',
])]
final class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use HasFactory;

    use HasUuidRouteKey;

    protected $casts = [
        'type' => StockMovementType::class,
        'input_unit' => MeasurementUnit::class,
        'input_quantity' => 'decimal:3',
        'quantity_delta' => 'decimal:3',
        'quantity_before' => 'decimal:3',
        'quantity_after' => 'decimal:3',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function householdProduct(): BelongsTo
    {
        return $this->belongsTo(HouseholdProduct::class);
    }

    public function storageLocation(): BelongsTo
    {
        return $this->belongsTo(StorageLocation::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
