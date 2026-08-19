<?php

namespace App\Actions\Households;

use App\Contracts\Households\HouseholdRepository;
use App\DTO\Households\LeaveHouseholdData;
use App\DTO\Households\LeaveHouseholdResult;
use App\Enums\HouseholdRole;
use Illuminate\Support\Facades\DB;

final readonly class LeaveHouseholdAction
{
    public function __construct(private HouseholdRepository $households) {}

    public function handle(LeaveHouseholdData $data): LeaveHouseholdResult
    {
        return DB::transaction(function () use ($data): LeaveHouseholdResult {
            $household = $this->households->findByUuidForUpdate($data->uuid);
            $memberships = $this->households->findMembershipsForHouseholdForUpdate($household);
            $actor = $memberships->firstWhere('user_id', $data->actorUserId);

            if ($actor === null) {
                $this->households->findMembershipForUpdate($household, $data->actorUserId);
            }

            if ($actor->role === HouseholdRole::Member) {
                $this->households->deleteMembership($actor);

                return new LeaveHouseholdResult($household->uuid, false, null);
            }

            $newOwner = $memberships->first(
                fn ($membership) => $membership->role === HouseholdRole::Member,
            );

            if ($newOwner === null) {
                $this->households->delete($household);

                return new LeaveHouseholdResult($household->uuid, true, null);
            }

            $this->households->deleteMembership($actor);
            $this->households->changeRole($newOwner, HouseholdRole::Owner);

            return new LeaveHouseholdResult(
                $household->uuid,
                false,
                $newOwner->user_id,
            );
        });
    }
}
