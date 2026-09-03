<?php

namespace App\Repositories;

use App\Contracts\Recipes\RecipeRepository;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentRecipeRepository implements RecipeRepository
{
    public function paginate(int $perPage): LengthAwarePaginator
    {
        return Recipe::query()
            ->with('creator')
            ->withCount(['ingredients', 'steps'])
            ->orderBy('title')
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function findByUuid(string $recipeUuid): Recipe
    {
        return Recipe::query()
            ->with([
                'creator',
                'ingredients.product',
                'steps',
            ])
            ->where('uuid', $recipeUuid)
            ->firstOrFail();
    }

    public function findByUuidForUpdate(string $recipeUuid): Recipe
    {
        return Recipe::query()
            ->where('uuid', $recipeUuid)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function create(
        ?User $creator,
        string $title,
        ?string $description,
        int $servings,
        int $beforeCookingMinutes,
        int $cookingMinutes,
        ?string $imagePath = null
    ): Recipe {
        return Recipe::query()->create([
            'created_by_user_id' => $creator?->getKey(),
            'title' => $title,
            'description' => $description,
            'servings' => $servings,
            'before_cooking_minutes' => $beforeCookingMinutes,
            'cooking_minutes' => $cookingMinutes,
            'image_path' => $imagePath,
        ]);
    }

    public function update(
        Recipe $recipe,
        string $title,
        ?string $description,
        int $servings,
        int $beforeCookingMinutes,
        int $cookingMinutes,
        ?string $imagePath = null
    ): void {
        $recipe->updateOrFail([
            'title' => $title,
            'description' => $description,
            'servings' => $servings,
            'before_cooking_minutes' => $beforeCookingMinutes,
            'cooking_minutes' => $cookingMinutes,
            'image_path' => $imagePath,
        ]);
    }

    public function delete(Recipe $recipe): void
    {
        $recipe->deleteOrFail();
    }

    public function hasProduct(
        Recipe $recipe,
        Product $product,
        ?RecipeIngredient $ignore = null
    ): bool {
        return $recipe->ingredients()
            ->where('product_id', $product->getKey())
            ->when($ignore !== null,
                fn ($query) => $query->whereKeyNot($ignore->getKey())
            )
            ->exists();
    }

    public function findIngredientForUpdate(
        Recipe $recipe,
        string $ingredientUuid
    ): RecipeIngredient {
        return $recipe->ingredients()
            ->where('uuid', $ingredientUuid)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function createIngredient(
        Recipe $recipe,
        Product $product,
        string $quantity,
        int $position,
        bool $isOptional,
        ?string $note
    ): RecipeIngredient {
        return $recipe->ingredients()->create([
            'product_id' => $product->getKey(),
            'quantity' => $quantity,
            'is_optional' => $isOptional,
            'position' => $position,
            'note' => $note,
        ]);
    }

    public function nextIngredientPosition(Recipe $recipe): int
    {
        return ((int) $recipe->ingredients()->reorder()->max('position')) + 1;
    }

    public function updateIngredient(
        RecipeIngredient $ingredient,
        Product $product,
        string $quantity,
        int $position,
        bool $isOptional,
        ?string $note
    ): void {
        $ingredient->updateOrFail([
            'product_id' => $product->getKey(),
            'quantity' => $quantity,
            'position' => $position,
            'is_optional' => $isOptional,
            'note' => $note,
        ]);
    }

    public function deleteIngredient(RecipeIngredient $ingredient): void
    {
        $ingredient->deleteOrFail();
    }

    public function findStepForUpdate(Recipe $recipe, string $stepUuid): RecipeStep
    {
        return $recipe->steps()
            ->where('uuid', $stepUuid)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function createStep(
        Recipe $recipe,
        int $position,
        string $description
    ): RecipeStep {
        return $recipe->steps()->create([
            'position' => $position,
            'description' => $description,
        ]);
    }

    public function nextStepPosition(Recipe $recipe): int
    {
        return ((int) $recipe->steps()->reorder()->max('position')) + 1;
    }

    public function updateStep(
        RecipeStep $step,
        int $position,
        string $description
    ): void {
        $step->updateOrFail([
            'position' => $position,
            'description' => $description,
        ]);
    }

    public function deleteStep(RecipeStep $step): void
    {
        $step->deleteOrFail();
    }
}
