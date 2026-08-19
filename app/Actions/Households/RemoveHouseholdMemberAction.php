<?php

namespace App\Actions\Households;

use App\Contracts\Households\HouseholdRepository;
use App\DTO\Households\RemoveHouseholdMemberData;
use App\Enums\HouseholdRole;
use App\Exceptions\Households\HouseholdAccessDenied;
use App\Exceptions\Households\HouseholdMembershipConflict;
use Illuminate\Support\Facades\DB;

final readonly class RemoveHouseholdMemberAction
{
    public function __construct(private HouseholdRepository $households) {}

    public function handle(RemoveHouseholdMemberData $data): void
    {
        DB::transaction(function () use ($data): void {
            $household = $this->households->findByUuidForUpdate($data->uuid);
            $memberships = $this->households->findMembershipsForHouseholdForUpdate($household);
            $actor = $memberships->firstWhere('user_id', $data->actorUserId);
            $target = $memberships->firstWhere('user_id', $data->memberUserId);

            if ($actor === null || $actor->role !== HouseholdRole::Owner) {
                throw new HouseholdAccessDenied('Only the household owner can remove members.');
            }

            if ($target === null) {
                $this->households->findMembershipForUpdate($household, $data->memberUserId);
            }

            if ($target->role === HouseholdRole::Owner) {
                throw new HouseholdMembershipConflict(
                    'The household owner cannot be removed through the members endpoint.',
                );
            }

            $this->households->deleteMembership($target);
        });
    }
}
