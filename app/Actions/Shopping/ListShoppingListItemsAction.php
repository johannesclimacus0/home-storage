<?php

namespace App\Actions\Shopping;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Shopping\ShoppingListRepository;
use App\Models\ShoppingListItem;
use Illuminate\Support\Collection;

final readonly class ListShoppingListItemsAction
{
    public function __construct(
        private HouseholdRepository $households,
        private ShoppingListRepository $shoppingList
    ) {}

    /** @return Collection<int, ShoppingListItem> */
    public function handle(string $householdUuid, int $actorUserId): Collection
    {
        $household = $this->households->findByUuid($householdUuid);
        $this->households->findMembership($household, $actorUserId);

        return $this->shoppingList->itemsForHousehold($household);
    }
}
