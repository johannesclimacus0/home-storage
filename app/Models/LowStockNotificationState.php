<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_membership_id
 * @property int $household_product_id
 * @property CarbonImmutable $last_notified_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read \App\Models\HouseholdMembership $householdMembership
 * @property-read \App\Models\HouseholdProduct $householdProduct
 * @method static \Database\Factories\LowStockNotificationStateFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LowStockNotificationState newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LowStockNotificationState newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LowStockNotificationState query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LowStockNotificationState whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LowStockNotificationState whereHouseholdMembershipId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LowStockNotificationState whereHouseholdProductId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LowStockNotificationState whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LowStockNotificationState whereLastNotifiedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|LowStockNotificationState whereUpdatedAt($value)
 * @mixin \Eloquent
 */
#[Fillable(['household_membership_id', 'household_product_id', 'last_notified_at'])]
class LowStockNotificationState extends Model
{
    use HasFactory;

    protected $casts = [
        'last_notified_at' => 'immutable_datetime',
    ];

    public function householdMembership(): BelongsTo
    {
        return $this->belongsTo(HouseholdMembership::class);
    }

    public function householdProduct(): BelongsTo
    {
        return $this->belongsTo(HouseholdProduct::class);
    }
}
