<?php

namespace App\Contracts\Shopping;

use App\Models\Household;
use App\Models\Product;
use App\Models\ShoppingListItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;

interface ShoppingListRepository
{
    /**
     * @return Collection<int, ShoppingListItem>
     */
    public function itemsForHousehold(Household $household): Collection;

    public function findForHousehold(
        Household $household,
        string $itemUuid
    ): ShoppingListItem;

    public function findForHouseholdForUpdate(
        Household $household,
        string $itemUuid
    ): ShoppingListItem;

    public function findByProductForUpdate(
        Household $household,
        Product $product
    ): ?ShoppingListItem;

    public function create(
        Household $household,
        Product $product,
        User $addedBy,
        string $quantity
    ): ShoppingListItem;

    public function updateQuantity(
        ShoppingListItem $item,
        string $quantity
    ): void;

    public function markCompleted(
        ShoppingListItem $item,
        CarbonImmutable $completedAt
    ): void;

    public function markIncomplete(
        ShoppingListItem $item
    ): void;

    public function delete(
        ShoppingListItem $item
    ): void;
}
