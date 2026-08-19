<?php

namespace App\Actions\Households;

use App\Contracts\Households\HouseholdRepository;
use App\DTO\Households\TransferHouseholdOwnershipData;
use App\DTO\Households\TransferHouseholdOwnershipResult;
use App\Enums\HouseholdRole;
use App\Exceptions\Households\HouseholdAccessDenied;
use App\Exceptions\Households\HouseholdMembershipConflict;
use Illuminate\Support\Facades\DB;

final readonly class TransferHouseholdOwnershipAction
{
    public function __construct(private HouseholdRepository $households) {}

    public function handle(TransferHouseholdOwnershipData $data): TransferHouseholdOwnershipResult
    {
        if ($data->currentOwnerUserId === $data->newOwnerUserId) {
            throw new HouseholdMembershipConflict('The owner cannot transfer ownership to themselves.');
        }

        return DB::transaction(function () use ($data): TransferHouseholdOwnershipResult {
            $household = $this->households->findByUuidForUpdate($data->uuid);

            $owner = $this->households->findMembershipForUpdate(
                $household,
                $data->currentOwnerUserId,
            );

            $newOwner = $this->households->findMembershipForUpdate(
                $household,
                $data->newOwnerUserId,
            );

            if ($owner->role !== HouseholdRole::Owner) {
                throw new HouseholdAccessDenied('The current user is not the household owner.');
            }

            if ($newOwner->role !== HouseholdRole::Member) {
                throw new HouseholdMembershipConflict('The new owner is not a household member.');
            }

            $this->households->changeRole($owner, HouseholdRole::Member);
            $this->households->changeRole($newOwner, HouseholdRole::Owner);

            return new TransferHouseholdOwnershipResult(
                uuid: $household->uuid,
                newOwnerUserId: $newOwner->user_id,
            );
        });
    }
}
