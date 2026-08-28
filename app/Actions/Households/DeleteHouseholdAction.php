<?php

namespace App\Actions\Households;

use App\Contracts\Households\HouseholdRepository;
use App\Enums\HouseholdRole;
use App\Exceptions\Households\HouseholdAccessDenied;
use Illuminate\Support\Facades\DB;

final readonly class DeleteHouseholdAction
{
    public function __construct(private HouseholdRepository $households) {}

    public function handle(string $uuid, int $actorUserId): void
    {
        DB::transaction(function () use ($uuid, $actorUserId): void {
            $household = $this->households->findByUuidForUpdate($uuid);
            $actor = $this->households->findMembershipForUpdate($household, $actorUserId);

            if ($actor->role !== HouseholdRole::Owner) {
                throw new HouseholdAccessDenied(__('messages.households.owner_only_delete'));
            }

            $this->households->delete($household);
        });
    }
}
