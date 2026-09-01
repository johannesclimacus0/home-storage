<?php

namespace App\DTO\Recipes;

use App\Models\Recipe;
use Illuminate\Support\Collection;

final readonly class RecipeAvailabilityData
{
    /**
     * @param  Collection<int, RecipeIngredientAvailabilityData>  $ingredients
     */
    public function __construct(
        public Recipe $recipe,
        public Collection $ingredients,
        public bool $canMake,
        public int $missingRequiredCount
    ) {}

    /**
     * @return Collection<int, RecipeIngredientAvailabilityData>
     */
    public function missingRequiredIngredients(): Collection
    {
        return $this->ingredients->filter(
            fn (RecipeIngredientAvailabilityData $ingredient) => !$ingredient->ingredient->is_optional && !$ingredient->sufficient
        )->values();
    }
}
