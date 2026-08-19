<?php

namespace App\Contracts\Households;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

interface HouseholdRepository
{
    public function create(string $name): Household;

    public function addMember(
        Household $household,
        int $userId,
        HouseholdRole $role = HouseholdRole::Member
    ): HouseholdMembership;

    public function findByUuidForUpdate(string $uuid): Household;

    public function findByUuid(string $uuid): Household;

    public function findMembershipForUpdate(
        Household $household,
        int $userId
    ): HouseholdMembership;

    public function findMembership(
        Household $household,
        int $userId,
    ): HouseholdMembership;

    public function changeRole(
        HouseholdMembership $membership,
        HouseholdRole $role
    ): void;

    public function findUserByEmail(string $email): User;

    public function membershipExists(
        Household $household,
        int $userId,
    ): bool;

    /**
     * @return Collection<int, HouseholdMembership>
     */
    public function findMembershipsForUser(int $userId): Collection;
}
