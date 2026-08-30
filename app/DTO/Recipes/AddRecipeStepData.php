<?php

namespace App\DTO\Recipes;

final readonly class AddRecipeStepData
{
    public function __construct(
        public string $recipeUuid,
        public int $actorUserId,
        public string $description
    ) {}
}
