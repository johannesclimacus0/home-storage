<?php

namespace App\Repositories;

use App\Contracts\Shopping\ShoppingListRepository;
use App\Models\Household;
use App\Models\Product;
use App\Models\ShoppingListItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

final class EloquentShoppingListRepository implements ShoppingListRepository
{

    public function itemsForHousehold(Household $household): Collection
    {
        return $household->shoppingListItems()
            ->with(['product', 'addedBy'])
            ->orderByRaw(
                'CASE WHEN completed_at IS NULL THEN 0 ELSE 1 END'
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }

    public function findForHousehold(Household $household, string $itemUuid): ShoppingListItem
    {
        return $household->shoppingListItems()
            ->where('uuid', $itemUuid)
            ->firstOrFail();
    }

    public function findForHouseholdForUpdate(Household $household, string $itemUuid): ShoppingListItem
    {
        return $household->shoppingListItems()
            ->where('uuid', $itemUuid)
            ->with('product')
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function findByProductForUpdate(Household $household, Product $product): ?ShoppingListItem {
        return $household->shoppingListItems()
            ->where('product_id', $product->getKey())
            ->lockForUpdate()
            ->first();
    }

    public function create(Household $household, Product $product, User $addedBy, string $quantity): ShoppingListItem
    {
        return $household->shoppingListItems()->create([
            'product_id' => $product->getKey(),
            'added_by_user_id' => $addedBy->getKey(),
            'quantity' => $quantity,
            'completed_at' => null,
        ]);
    }

    public function updateQuantity(ShoppingListItem $item, string $quantity): void
    {
        $item->updateOrFail(['quantity' => $quantity]);
    }

    public function markCompleted(ShoppingListItem $item, CarbonImmutable $completedAt): void
    {
        $item->updateOrFail(['completed_at' => $completedAt]);
    }

    public function markIncomplete(ShoppingListItem $item): void
    {
        $item->updateOrFail(['completed_at' => null]);
    }

    public function delete(ShoppingListItem $item): void
    {
        $item->deleteOrFail();
    }
}
