<?php

namespace App\Actions\Households;

use App\Contracts\Households\HouseholdRepository;
use App\Models\HouseholdMembership;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListUserHouseholdsAction
{
    public function __construct(private HouseholdRepository $households) {}

    /**
     * @return Collection<int, HouseholdMembership>
     */
    public function handle(int $userId): Collection
    {
        return $this->households->findMembershipsForUser($userId);
    }
}
