<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class RecipeIngredientResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'product' => new ProductResource($this->whenLoaded('product')),
            'quantity' => $this->quantity,
            'position' => $this->position,
            'is_optional' => $this->is_optional,
            'note' => $this->note,
        ];
    }
}
