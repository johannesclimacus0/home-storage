<?php

namespace App\Models;

use App\Traits\HasUuidRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\HouseholdMembership> $householdMemberships
 * @property-read int|null $household_memberships_count
 * @method static \Database\Factories\HouseholdFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereUuid($value)
 * @mixin \Eloquent
 */
#[Fillable(['name'])]
class Household extends Model
{
    /** @use HasFactory<\Database\Factories\HouseholdFactory> */
    use HasFactory, HasUuidRouteKey;

    public function householdMemberships(): HasMany
    {
        return $this->hasMany(HouseholdMembership::class);
    }
}
