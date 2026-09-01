<?php

namespace App\Http\Resources;

use App\DTO\Recipes\RecipeIngredientAvailabilityData;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RecipeAvailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'recipe_uuid' => $this->recipe->uuid,
            'can_make' => $this->canMake,
            'missing_required_count' => $this->missingRequiredCount,
            'ingredients' => $this->ingredients->map(
                fn (RecipeIngredientAvailabilityData $availability) => [
                    'ingredient_uuid' => $availability->ingredient->uuid,
                    'product' => [
                        'uuid' => $availability->ingredient->product->uuid,
                        'name' => $availability->ingredient->product->name,
                        'measurement_type' => $availability->ingredient->product->measurement_type->value,
                    ],
                    'required_quantity' => $availability->ingredient->quantity,
                    'available_quantity' => $availability->availableQuantity,
                    'missing_quantity' => $availability->missingQuantity,
                    'is_optional' => $availability->ingredient->is_optional,
                    'sufficient' => $availability->sufficient,
                ]
            )->values()->all(),
        ];
    }
}
