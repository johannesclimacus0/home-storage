<?php

namespace App\DTO\Recipes;

final readonly class CreateRecipeData
{
    public function __construct(
        public int $actorUserId,
        public string $title,
        public ?string $description,
        public int $servings,
        public int $beforeCookingMinutes,
        public int $cookingMinutes
    ) {}
}
