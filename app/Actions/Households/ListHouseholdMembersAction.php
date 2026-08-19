<?php

namespace App\Actions\Households;

use App\Contracts\Households\HouseholdRepository;
use App\Models\HouseholdMembership;
use Illuminate\Database\Eloquent\Collection;

final readonly class ListHouseholdMembersAction
{
    public function __construct(private HouseholdRepository $households) {}

    /** @return Collection<int, HouseholdMembership> */
    public function handle(string $uuid, int $actorUserId): Collection
    {
        $household = $this->households->findByUuid($uuid);
        $this->households->findMembership($household, $actorUserId);

        return $this->households->findMembershipsForHousehold($household);
    }
}
