<?php

namespace App\DTO\Recipes;

use App\Models\RecipeIngredient;

final readonly class RecipeIngredientAvailabilityData
{
    public function __construct(
        public RecipeIngredient $ingredient,
        public string $availableQuantity,
        public string $missingQuantity,
        public bool $sufficient
    ) {}
}
