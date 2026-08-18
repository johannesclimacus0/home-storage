<?php

namespace App\Actions\Households;

use App\Contracts\Households\HouseholdRepository;
use App\DTO\Households\CreateHouseholdData;
use App\DTO\Households\CreateHouseholdResult;
use App\Enums\HouseholdRole;
use Illuminate\Support\Facades\DB;

final readonly class CreateHouseholdAction
{
    public function __construct(private HouseholdRepository $households) {}

    public function handle(CreateHouseholdData $data): CreateHouseholdResult
    {
        return DB::transaction(function () use ($data): CreateHouseholdResult {
            $household = $this->households->create(
                name: trim($data->name),
            );

            $this->households->addMember(
                household: $household,
                userId: $data->userId,
                role: HouseholdRole::Owner,
            );

            return new CreateHouseholdResult(
                uuid: $household->uuid,
                name: $household->name,
                role: HouseholdRole::Owner,
            );
        });
    }
}
