<?php

namespace App\Actions\Shopping;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Shopping\ShoppingListRepository;
use App\Models\ShoppingListItem;

final readonly class ReopenShoppingListItemAction
{
    public function __construct(
        private HouseholdRepository $households,
        private ShoppingListRepository $shoppingList
    ) {}

    public function handle(string $householdUuid, int $actorUserId, string $itemUuid): ShoppingListItem
    {
        $household = $this->households->findByUuid($householdUuid);
        $this->households->findMembership($household, $actorUserId);
        $item = $this->shoppingList->findForHousehold($household, $itemUuid);
        $this->shoppingList->markIncomplete($item);

        return $item->refresh()->load(['product', 'addedBy']);
    }
}
