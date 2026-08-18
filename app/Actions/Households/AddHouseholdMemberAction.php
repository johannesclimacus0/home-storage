<?php

namespace App\Actions\Households;

use App\Contracts\Households\HouseholdRepository;
use App\DTO\Households\AddHouseholdMemberData;
use App\DTO\Households\AddHouseholdMemberResult;
use App\Enums\HouseholdRole;
use DomainException;
use Illuminate\Support\Facades\DB;

final readonly class AddHouseholdMemberAction
{
    public function __construct(private HouseholdRepository $households)
    {
    }

    public function handle(AddHouseholdMemberData $data): AddHouseholdMemberResult
    {
        return DB::transaction(function () use ($data): AddHouseholdMemberResult {
            $household = $this->households->findByUuidForUpdate($data->uuid);

            $owner = $this->households->findMembershipForUpdate(
                $household,
                $data->actorUserId,
            );

            if ($owner->role !== HouseholdRole::Owner) {
                throw new DomainException('Only the household owner can add members.');
            }

            $user = $this->households->findUserByEmail(
                mb_strtolower(trim($data->newMemberEmail)),
            );

            if ($user->getKey() === $data->actorUserId) {
                throw new DomainException('The owner is already a household member.');
            }

            if ($this->households->membershipExists($household, $user->getKey())) {
                throw new DomainException('The user is already a household member.');
            }

            $newMembership = $this->households->addMember(
                household: $household,
                userId: $user->getKey()
            );

            return new AddHouseholdMemberResult(
                uuid: $household->uuid,
                newMemberUserId: $newMembership->user_id,
            );
        });
    }
}
