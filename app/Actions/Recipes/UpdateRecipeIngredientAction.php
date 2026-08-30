<?php

namespace App\Actions\Recipes;

use App\Contracts\Products\ProductRepository;
use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Recipes\UpdateRecipeIngredientData;
use App\Exceptions\Inventory\InvalidStockQuantity;
use App\Exceptions\Recipes\RecipeIngredientConflict;
use App\Models\RecipeIngredient;
use App\Support\Inventory\StockQuantity;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class UpdateRecipeIngredientAction
{
    public function __construct(
        private RecipeRepository $recipes,
        private ProductRepository $products
    ) {}

    public function handle(UpdateRecipeIngredientData $data): RecipeIngredient
    {
        return DB::transaction(function () use ($data): RecipeIngredient {
            $recipe = $this->recipes->findByUuidForUpdate($data->recipeUuid);

            if ($recipe->created_by_user_id === null
                || $recipe->created_by_user_id !== $data->actorUserId) {
                throw new AuthorizationException;
            }

            $ingredient = $this->recipes->findIngredientForUpdate(
                $recipe,
                $data->ingredientUuid
            );
            $product = $this->products->findByUuid($data->productUuid);

            if ($this->recipes->hasProduct($recipe, $product, $ingredient)) {
                throw new RecipeIngredientConflict(__('messages.recipes.ingredient_duplicate'));
            }

            $quantity = StockQuantity::toBaseUnit(
                $data->quantity,
                $data->unit,
                $product->measurement_type
            );

            if ($quantity === '0.000') {
                throw new InvalidStockQuantity(__('messages.recipes.ingredient_quantity_positive'));
            }

            $this->recipes->updateIngredient(
                ingredient: $ingredient,
                product: $product,
                quantity: $quantity,
                position: $ingredient->position,
                isOptional: $data->isOptional,
                note: $this->normalizeNote($data->note)
            );

            return $ingredient->refresh()->load('product');
        });
    }

    private function normalizeNote(?string $note): ?string
    {
        $note = $note === null ? null : trim($note);

        return $note === '' ? null : $note;
    }
}
