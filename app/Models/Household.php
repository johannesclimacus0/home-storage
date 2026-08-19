<?php

namespace App\Models;

use App\Traits\HasUuidRouteKey;
use Database\Factories\HouseholdFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $uuid
 * @property string $name
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Collection<int, HouseholdMembership> $householdMemberships
 * @property-read int|null $household_memberships_count
 * @property-read Collection<int, HouseholdProduct> $householdProducts
 * @property-read int|null $household_products_count
 * @property-read Collection<int, StorageLocation> $storageLocations
 * @property-read int|null $storage_locations_count
 *
 * @method static \Database\Factories\HouseholdFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Household whereUuid($value)
 *
 * @mixin \Eloquent
 */
#[Fillable(['name'])]
class Household extends Model
{
    /** @use HasFactory<HouseholdFactory> */
    use HasFactory, HasUuidRouteKey;

    public function householdMemberships(): HasMany
    {
        return $this->hasMany(HouseholdMembership::class);
    }

    public function householdProducts(): HasMany
    {
        return $this->hasMany(HouseholdProduct::class);
    }

    public function storageLocations(): HasMany
    {
        return $this->hasMany(StorageLocation::class);
    }
}
