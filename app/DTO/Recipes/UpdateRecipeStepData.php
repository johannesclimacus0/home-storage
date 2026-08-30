<?php

namespace App\DTO\Recipes;

final readonly class UpdateRecipeStepData
{
    public function __construct(
        public string $recipeUuid,
        public string $stepUuid,
        public int $actorUserId,
        public string $description
    ) {}
}
