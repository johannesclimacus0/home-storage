<?php

namespace App\Policies;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\User;

final class HouseholdPolicy
{
    public function view(User $user, Household $household): bool
    {
        return $this->isMember($user, $household);
    }

    public function update(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, HouseholdRole::Owner);
    }

    public function delete(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, HouseholdRole::Owner);
    }

    public function addMember(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, HouseholdRole::Owner);
    }

    public function transferOwnership(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, HouseholdRole::Owner);
    }

    public function removeMember(User $user, Household $household): bool
    {
        return $this->hasRole($user, $household, HouseholdRole::Owner);
    }

    public function leave(User $user, Household $household): bool
    {
        return $this->isMember($user, $household);
    }

    public function manageInventory(User $user, Household $household): bool
    {
        return $this->isMember($user, $household);
    }

    private function hasRole(User $user, Household $household, HouseholdRole $role): bool
    {
        return $household->householdMemberships()
            ->where('user_id', $user->getKey())
            ->where('role', $role)
            ->exists();
    }

    private function isMember(User $user, Household $household): bool
    {
        return $household->householdMemberships()
            ->where('user_id', $user->getKey())
            ->exists();
    }
}
