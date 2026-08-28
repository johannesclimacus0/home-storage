<?php

namespace App\Actions\Shopping;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Shopping\ShoppingListRepository;
use App\DTO\Shopping\UpdateShoppingListItemData;
use App\Models\ShoppingListItem;
use App\Support\Inventory\StockQuantity;
use Illuminate\Support\Facades\DB;

final readonly class UpdateShoppingListItemAction
{
    public function __construct(
        private HouseholdRepository $households,
        private ShoppingListRepository $shoppingList
    ) {}

    public function handle(UpdateShoppingListItemData $data): ShoppingListItem
    {
        return DB::transaction(function () use ($data): ShoppingListItem {
            $household = $this->households->findByUuidForUpdate($data->householdUuid);
            $this->households->findMembershipForUpdate($household, $data->actorUserId);
            $item = $this->shoppingList->findForHousehold($household, $data->itemUuid);
            $item->loadMissing('product');
            $quantity = StockQuantity::toBaseUnit(
                $data->quantity,
                $data->unit,
                $item->product->measurement_type
            );
            $this->shoppingList->updateQuantity($item, $quantity);

            return $item->refresh()->load(['product', 'addedBy']);
        });
    }
}
