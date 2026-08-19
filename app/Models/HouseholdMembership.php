<?php

namespace App\Models;

use App\Enums\HouseholdRole;
use Database\Factories\HouseholdMembershipFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $household_id
 * @property int $user_id
 * @property HouseholdRole $role
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Household $household
 * @property-read User $user
 *
 * @method static \Database\Factories\HouseholdMembershipFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMembership newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMembership newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMembership query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMembership whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMembership whereHouseholdId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMembership whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMembership whereRole($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMembership whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|HouseholdMembership whereUserId($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['household_id', 'user_id', 'role'])]
class HouseholdMembership extends Model
{
    /** @use HasFactory<HouseholdMembershipFactory> */
    use HasFactory;

    protected $casts = [
        'role' => HouseholdRole::class,
    ];

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
