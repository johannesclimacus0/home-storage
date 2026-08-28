<?php

namespace App\Actions\Shopping;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Products\ProductRepository;
use App\Contracts\Shopping\ShoppingListRepository;
use App\DTO\Shopping\AddShoppingListItemData;
use App\Models\ShoppingListItem;
use App\Models\User;
use App\Support\Inventory\StockQuantity;
use Illuminate\Support\Facades\DB;

final readonly class AddShoppingListItemAction
{
    public function __construct(
        private HouseholdRepository $households,
        private ProductRepository $products,
        private ShoppingListRepository $shoppingList
    ) {}

    public function handle(AddShoppingListItemData $data): ShoppingListItem
    {
        return DB::transaction(function () use ($data): ShoppingListItem {
            $household = $this->households->findByUuidForUpdate($data->householdUuid);
            $this->households->findMembershipForUpdate($household, $data->actorUserId);
            $product = $this->products->findByUuid($data->productUuid);
            $quantity = StockQuantity::toBaseUnit(
                $data->quantity,
                $data->unit,
                $product->measurement_type
            );
            $item = $this->shoppingList->findByProductForUpdate($household, $product);

            if ($item === null) {
                $item = $this->shoppingList->create(
                    $household,
                    $product,
                    User::query()->findOrFail($data->actorUserId),
                    $quantity
                );
            } else {
                $this->shoppingList->updateQuantity($item, $quantity);
                $this->shoppingList->markIncomplete($item);
            }

            return $item->refresh()->load(['product', 'addedBy']);
        });
    }
}
