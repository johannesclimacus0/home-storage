<?php

namespace App\DTO\Recipes;

final readonly class DeleteRecipeData
{
    public function __construct(
        public string $recipeUuid,
        public int $actorUserId
    ) {}
}
