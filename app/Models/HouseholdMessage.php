<?php

namespace App\Models;

use App\Traits\HasUuidRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $uuid
 * @property int $household_id
 * @property int $sender_id
 * @property string $content
 * @property \Carbon\CarbonImmutable|null $edited_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Carbon\CarbonImmutable|null $deleted_at
 * @property-read \App\Models\Household $household
 * @property-read \App\Models\User $sender
 * @method static \Database\Factories\HouseholdMessageFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage onlyTrashed()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage whereDeletedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage whereEditedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage whereHouseholdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage whereSenderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage whereUuid($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage withTrashed(bool $withTrashed = true)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMessage withoutTrashed()
 * @mixin \Eloquent
 */
#[Fillable([
    'household_id',
    'sender_id',
    'content',
    'edited_at',
])]
class HouseholdMessage extends Model
{
    /** @use HasFactory<\Database\Factories\HouseholdMessageFactory> */
    use HasFactory, HasUuidRouteKey, SoftDeletes;

    protected $casts = [
        'edited_at' => 'immutable_datetime',
        'deleted_at' => 'immutable_datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
