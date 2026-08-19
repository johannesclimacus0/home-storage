<?php

namespace App\Repositories;

use App\Contracts\Households\HouseholdRepository;
use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

final class EloquentHouseholdRepository implements HouseholdRepository
{
    public function create(string $name): Household
    {
        return Household::query()->create([
            'name' => $name,
        ]);
    }

    public function addMember(
        Household $household,
        int $userId,
        HouseholdRole $role = HouseholdRole::Member
    ): HouseholdMembership {
        return $household->householdMemberships()->create([
            'user_id' => $userId,
            'role' => $role,
        ]);
    }

    public function findUserByEmail(string $email): User
    {
        return User::query()
            ->where('email', $email)
            ->firstOrFail();
    }

    public function membershipExists(Household $household, int $userId): bool
    {
        return HouseholdMembership::query()
            ->where('household_id', $household->getKey())
            ->where('user_id', $userId)
            ->exists();
    }

    public function findByUuidForUpdate(string $uuid): Household
    {
        return Household::query()
            ->where('uuid', $uuid)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function findByUuid(string $uuid): Household
    {
        return Household::query()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function changeRole(HouseholdMembership $membership, HouseholdRole $role): void
    {
        $membership->updateOrFail([
            'role' => $role,
        ]);
    }

    public function findMembershipForUpdate(Household $household, int $userId): HouseholdMembership
    {
        return HouseholdMembership::query()
            ->where('user_id', $userId)
            ->where('household_id', $household->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function findMembership(Household $household, int $userId): HouseholdMembership
    {
        return HouseholdMembership::query()
            ->where('user_id', $userId)
            ->where('household_id', $household->getKey())
            ->firstOrFail();
    }

    /**
     * @return Collection<int, HouseholdMembership>
     */
    public function findMembershipsForUser(int $userId): Collection
    {
        return HouseholdMembership::query()
            ->where('user_id', $userId)
            ->with('household')
            ->latest()
            ->get();
    }
}
