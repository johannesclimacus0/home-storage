<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use Illuminate\Support\Facades\DB;

final readonly class RemoveProductFromHouseholdAction
{
    public function __construct(
        private HouseholdRepository $households,
        private InventoryRepository $inventory,
    ) {}

    public function handle(string $householdUuid, int $actorUserId, string $productUuid): void
    {
        DB::transaction(function () use ($householdUuid, $actorUserId, $productUuid): void {
            $household = $this->households->findByUuidForUpdate($householdUuid);
            $this->households->findMembershipForUpdate($household, $actorUserId);
            $householdProduct = $this->inventory->findHouseholdProductForUpdate(
                $household,
                $productUuid,
            );

            $this->inventory->deleteHouseholdProduct($householdProduct);
        });
    }
}
