<?php

namespace App\Actions\Households;

use App\Contracts\Households\HouseholdRepository;
use App\DTO\Households\UpdateHouseholdData;
use App\Enums\HouseholdRole;
use App\Exceptions\Households\HouseholdAccessDenied;
use App\Models\Household;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateHouseholdAction
{
    public function __construct(private HouseholdRepository $households) {}

    public function handle(UpdateHouseholdData $data): Household
    {
        return DB::transaction(function () use ($data): Household {
            $name = trim($data->name);

            if ($name === '') {
                throw new InvalidArgumentException(__('messages.households.name_required'));
            }

            $household = $this->households->findByUuidForUpdate($data->uuid);
            $actor = $this->households->findMembershipForUpdate(
                $household,
                $data->actorUserId,
            );

            if ($actor->role !== HouseholdRole::Owner) {
                throw new HouseholdAccessDenied(__('messages.households.owner_only_update'));
            }

            $this->households->update($household, $name);

            return $this->households->findByUuidWithMembers($household->uuid);
        });
    }
}
