<?php

namespace App\DTO\Recipes;

use App\Enums\MeasurementUnit;

final readonly class UpdateRecipeIngredientData
{
    public function __construct(
        public string $recipeUuid,
        public string $ingredientUuid,
        public int $actorUserId,
        public string $productUuid,
        public string $quantity,
        public MeasurementUnit $unit,
        public bool $isOptional,
        public ?string $note
    ) {}
}
