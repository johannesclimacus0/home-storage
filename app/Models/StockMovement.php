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
