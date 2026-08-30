<?php

namespace App\DTO\Recipes;

final readonly class UpdateRecipeData
{
    public function __construct(
        public string $recipeUuid,
        public int $actorUserId,
        public string $title,
        public ?string $description,
        public int $servings,
        public int $beforeCookingMinutes,
        public int $cookingMinutes
    ) {}
}
