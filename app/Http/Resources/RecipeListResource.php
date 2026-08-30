<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RecipeListResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'servings' => $this->servings,
            'before_cooking_minutes' => $this->before_cooking_minutes,
            'cooking_minutes' => $this->cooking_minutes,
            'creator' => $this->whenLoaded('creator', function () {
                if ($this->creator === null) {
                    return null;
                }

                return [
                    'id' => $this->creator->getKey(),
                    'name' => $this->creator->name,
                ];
            }),
            'ingredients_count' => $this->whenCounted('ingredients'),
            'steps_count' => $this->whenCounted('steps'),
        ];
    }
}
