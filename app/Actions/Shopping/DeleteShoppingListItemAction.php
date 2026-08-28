<?php

namespace App\Actions\Shopping;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Shopping\ShoppingListRepository;

final readonly class DeleteShoppingListItemAction
{
    public function __construct(
        private HouseholdRepository $households,
        private ShoppingListRepository $shoppingList
    ) {}

    public function handle(string $householdUuid, int $actorUserId, string $itemUuid): void
    {
        $household = $this->households->findByUuid($householdUuid);
        $this->households->findMembership($household, $actorUserId);
        $item = $this->shoppingList->findForHousehold($household, $itemUuid);
        $this->shoppingList->delete($item);
    }
}
