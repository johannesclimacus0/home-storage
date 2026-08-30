<?php

namespace App\DTO\Recipes;

final readonly class DeleteRecipeStepData
{
    public function __construct(
        public string $recipeUuid,
        public string $stepUuid,
        public int $actorUserId
    ) {}
}
