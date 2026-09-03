<?php

namespace App\DTO\Recipes;

use Illuminate\Http\UploadedFile;

final readonly class UpdateRecipeData
{
    public function __construct(
        public string $recipeUuid,
        public int $actorUserId,
        public string $title,
        public ?string $description,
        public int $servings,
        public int $beforeCookingMinutes,
        public int $cookingMinutes,
        public ?UploadedFile $image,
        public bool $removeImage
    ) {}
}
