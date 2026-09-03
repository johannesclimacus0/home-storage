<?php

namespace App\Contracts\Recipes;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface RecipeRepository
{
    /**
     * @return LengthAwarePaginator<int, Recipe>
     */
    public function paginate(int $perPage): LengthAwarePaginator;

    public function findByUuid(string $recipeUuid): Recipe;

    public function findByUuidForUpdate(string $recipeUuid): Recipe;

    public function create(
        ?User $creator,
        string $title,
        ?string $description,
        int $servings,
        int $beforeCookingMinutes,
        int $cookingMinutes,
        ?string $imagePath = null
    ): Recipe;

    public function update(
        Recipe $recipe,
        string $title,
        ?string $description,
        int $servings,
        int $beforeCookingMinutes,
        int $cookingMinutes,
        ?string $imagePath = null
    ): void;

    public function delete(Recipe $recipe): void;

    public function hasProduct(
        Recipe $recipe,
        Product $product,
        ?RecipeIngredient $ignore = null
    ): bool;

    public function findIngredientForUpdate(
        Recipe $recipe,
        string $ingredientUuid
    ): RecipeIngredient;

    public function createIngredient(
        Recipe $recipe,
        Product $product,
        string $quantity,
        int $position,
        bool $isOptional,
        ?string $note
    ): RecipeIngredient;

    public function nextIngredientPosition(Recipe $recipe): int;

    public function updateIngredient(
        RecipeIngredient $ingredient,
        Product $product,
        string $quantity,
        int $position,
        bool $isOptional,
        ?string $note
    ): void;

    public function deleteIngredient(RecipeIngredient $ingredient): void;

    public function findStepForUpdate(Recipe $recipe, string $stepUuid): RecipeStep;

    public function createStep(
        Recipe $recipe,
        int $position,
        string $description
    ): RecipeStep;

    public function nextStepPosition(Recipe $recipe): int;

    public function updateStep(
        RecipeStep $step,
        int $position,
        string $description
    ): void;

    public function deleteStep(RecipeStep $step): void;
}
