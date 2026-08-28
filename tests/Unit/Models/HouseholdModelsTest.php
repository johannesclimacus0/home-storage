<?php

namespace Tests\Unit\Models;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class HouseholdModelsTest extends TestCase
{
    public function test_household_configuration_and_relationships(): void
    {
        $household = new Household;

        $this->assertSame(['name'], $household->getFillable());
        $this->assertSame('uuid', $household->getRouteKeyName());
        $this->assertInstanceOf(HasMany::class, $household->householdMemberships());
        $this->assertInstanceOf(HasMany::class, $household->householdProducts());
        $this->assertInstanceOf(HasMany::class, $household->storageLocations());
    }

    public function test_membership_configuration_casts_and_relationships(): void
    {
        $membership = new HouseholdMembership;
        $membership->setRawAttributes(['role' => 'owner']);

        $this->assertSame(
            [
                'household_id',
                'user_id',
                'role',
                'low_stock_reminders_enabled',
                'low_stock_reminder_interval_hours',
            ],
            $membership->getFillable(),
        );
        $this->assertSame(HouseholdRole::Owner, $membership->role);
        $this->assertInstanceOf(BelongsTo::class, $membership->household());
        $this->assertInstanceOf(BelongsTo::class, $membership->user());
    }

    public function test_user_exposes_household_memberships_relationship(): void
    {
        $user = new User;

        $this->assertSame(['name', 'email', 'password'], $user->getFillable());
        $this->assertInstanceOf(HasMany::class, $user->householdMemberships());
    }
}
