<?php

namespace App\Models;

use App\Traits\HasUuidRouteKey;
use Carbon\CarbonImmutable;
use Database\Factories\ShoppingListItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property int $household_id
 * @property int $product_id
 * @property int $added_by_user_id
 * @property numeric $quantity
 * @property CarbonImmutable|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $addedBy
 * @property-read Household $household
 * @property-read Product $product
 *
 * @method static \Database\Factories\ShoppingListItemFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingListItem newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingListItem newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingListItem query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingListItem whereAddedByUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingListItem whereCompletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingListItem whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingListItem whereHouseholdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingListItem whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingListItem whereProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingListItem whereQuantity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingListItem whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|ShoppingListItem whereUuid($value)
 *
 * @mixin \Eloquent
 */
#[Fillable([
    'uuid',
    'household_id',
    'product_id',
    'added_by_user_id',
    'quantity',
    'completed_at',
])]
class ShoppingListItem extends Model
{
    /** @use HasFactory<ShoppingListItemFactory> */
    use HasFactory, HasUuidRouteKey;

    protected $casts = [
        'quantity' => 'decimal:3',
        'completed_at' => 'immutable_datetime',
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function addedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'added_by_user_id');
    }
}
