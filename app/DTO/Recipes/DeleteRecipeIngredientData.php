<?php

namespace App\DTO\Recipes;

final readonly class DeleteRecipeIngredientData
{
    public function __construct(
        public string $recipeUuid,
        public string $ingredientUuid,
        public int $actorUserId
    ) {}
}
