<?php

namespace App\Actions\Households;

use App\Contracts\Households\HouseholdRepository;
use App\Models\Household;

final readonly class ShowHouseholdAction
{
    public function __construct(private HouseholdRepository $households) {}

    public function handle(string $uuid, int $actorUserId): Household
    {
        $household = $this->households->findByUuidWithMembers($uuid);
        $this->households->findMembership($household, $actorUserId);

        return $household;
    }
}
