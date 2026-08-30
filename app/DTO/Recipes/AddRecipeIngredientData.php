<?php

namespace App\DTO\Recipes;

use App\Enums\MeasurementUnit;

final readonly class AddRecipeIngredientData
{
    public function __construct(
        public string $recipeUuid,
        public int $actorUserId,
        public string $productUuid,
        public string $quantity,
        public MeasurementUnit $unit,
        public bool $isOptional,
        public ?string $note
    ) {}
}
