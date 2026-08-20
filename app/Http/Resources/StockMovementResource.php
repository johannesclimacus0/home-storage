<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StockMovementResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'type' => $this->type->value,
            'product' => [
                'uuid' => $this->product?->uuid,
                'name' => $this->product_name,
            ],
            'storage_location' => [
                'uuid' => $this->storageLocation?->uuid,
                'name' => $this->storage_location_name,
            ],
            'actor' => [
                'id' => $this->actor?->getKey(),
                'name' => $this->actor_name,
            ],
            'input' => [
                'quantity' => $this->input_quantity,
                'unit' => $this->input_unit->value,
            ],
            'quantity_delta' => $this->quantity_delta,
            'quantity_before' => $this->quantity_before,
            'quantity_after' => $this->quantity_after,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
