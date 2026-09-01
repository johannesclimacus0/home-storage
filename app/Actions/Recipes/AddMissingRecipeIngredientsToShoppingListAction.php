<?php

namespace App\Actions\Recipes;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Recipes\RecipeAvailabilityRepository;
use App\Contracts\Recipes\RecipeRepository;
use App\Contracts\Shopping\ShoppingListRepository;
use App\DTO\Recipes\RecipeIngredientAvailabilityData;
use App\Models\ShoppingListItem;
use App\Models\User;
use App\Services\Recipes\RecipeAvailabilityEvaluator;
use App\Support\Inventory\StockQuantity;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final readonly class AddMissingRecipeIngredientsToShoppingListAction
{
    public function __construct(
        private HouseholdRepository $households,
        private RecipeRepository $recipes,
        private RecipeAvailabilityRepository $availability,
        private RecipeAvailabilityEvaluator $evaluator,
        private ShoppingListRepository $shoppingList
    ) {}

    /**
     * @return Collection<int, ShoppingListItem>
     */
    public function handle(
        string $householdUuid,
        int $actorUserId,
        string $recipeUuid
    ): Collection {
        return DB::transaction(function () use ($householdUuid, $actorUserId, $recipeUuid) {
            $household = $this->households->findByUuidForUpdate($householdUuid);
            $this->households->findMembershipForUpdate($household, $actorUserId);
            $author = User::query()->findOrFail($actorUserId);
            $recipe = $this->recipes->findByUuid($recipeUuid);
            $productIds = $recipe->ingredients->pluck('product_id')->unique()->values();
            $quantities = $this->availability->quantitiesForProducts($household, $productIds);
            $missingIngredients = $this->evaluator
                ->evaluate($recipe, $quantities)
                ->missingRequiredIngredients();

            return $missingIngredients->map(function (RecipeIngredientAvailabilityData $missing) use ($household, $author) {
                $product = $missing->ingredient->product;
                $item = $this->shoppingList->findByProductForUpdate($household, $product);
                $quantity = $missing->missingQuantity;

                if ($item === null) {
                    $item = $this->shoppingList->create($household, $product, $author, $quantity);
                } else {
                    if (
                        $item->completed_at === null
                        && StockQuantity::toMinorUnits($item->quantity) > StockQuantity::toMinorUnits($quantity)
                    ) {
                        $quantity = $item->quantity;
                    }

                    $this->shoppingList->updateQuantity($item, $quantity);
                    $this->shoppingList->markIncomplete($item);
                }

                return $item->refresh()->load(['product', 'addedBy']);
            });
        });
    }
}
