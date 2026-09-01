<?php

namespace App\Services\Recipes;

use App\DTO\Recipes\RecipeAvailabilityData;
use App\DTO\Recipes\RecipeIngredientAvailabilityData;
use App\Models\Recipe;
use App\Support\Inventory\StockQuantity;

final class RecipeAvailabilityEvaluator
{
    public function evaluate(Recipe $recipe, array $quantitiesByProductId): RecipeAvailabilityData
    {
        $ingredients = $recipe->ingredients->map(function ($ingredient) use ($quantitiesByProductId) {
            $availableQuantity = StockQuantity::databaseValue(
                $quantitiesByProductId[$ingredient->product_id] ?? '0'
            );
            $requiredMinor = StockQuantity::toMinorUnits($ingredient->quantity);
            $availableMinor = StockQuantity::toMinorUnits($availableQuantity);
            $missingMinor = max($requiredMinor - $availableMinor, 0);

            return new RecipeIngredientAvailabilityData(
                ingredient: $ingredient,
                availableQuantity: $availableQuantity,
                missingQuantity: StockQuantity::fromMinorUnits($missingMinor),
                sufficient: $missingMinor === 0
            );
        });

        $missingRequiredCount = $ingredients->filter(
            fn (RecipeIngredientAvailabilityData $ingredient) => !$ingredient->ingredient->is_optional && !$ingredient->sufficient
        )->count();

        return new RecipeAvailabilityData(
            recipe: $recipe,
            ingredients: $ingredients,
            canMake: $missingRequiredCount === 0,
            missingRequiredCount: $missingRequiredCount
        );
    }
}
