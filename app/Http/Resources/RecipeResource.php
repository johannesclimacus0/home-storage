<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

final class RecipeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'title' => $this->title,
            'description' => $this->description,
            'image_url' => $this->image_path === null
                ? null
                : Storage::disk('public')->url($this->image_path),
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
            'ingredients' => RecipeIngredientResource::collection(
                $this->whenLoaded('ingredients')
            ),
            'steps' => RecipeStepResource::collection(
                $this->whenLoaded('steps')
            ),

            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
