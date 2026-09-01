<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class HouseholdRecipeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $recipe = $this->recipe;

        return [
            'uuid' => $recipe->uuid,
            'title' => $recipe->title,
            'description' => $recipe->description,
            'servings' => $recipe->servings,
            'before_cooking_minutes' => $recipe->before_cooking_minutes,
            'cooking_minutes' => $recipe->cooking_minutes,
            'creator' => $recipe->creator === null ? null : [
                'id' => $recipe->creator->getKey(),
                'name' => $recipe->creator->name,
            ],
            'ingredients_count' => $recipe->ingredients_count,
            'steps_count' => $recipe->steps_count,
            'availability' => [
                'can_make' => $this->canMake,
                'missing_required_count' => $this->missingRequiredCount,
            ],
        ];
    }
}
