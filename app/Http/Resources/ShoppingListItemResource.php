<?php

namespace App\Http\Resources;

use App\Enums\MeasurementType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ShoppingListItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'product' => [
                'uuid' => $this->product->uuid,
                'name' => $this->product->name,
                'measurement_type' => $this->product->measurement_type->value,
            ],
            'quantity' => $this->quantity,
            'unit' => match ($this->product->measurement_type) {
                MeasurementType::Mass => 'g',
                MeasurementType::Volume => 'ml',
                MeasurementType::Count => 'piece',
            },
            'completed_at' => $this->completed_at?->toISOString(),
            'added_by' => [
                'id' => $this->addedBy->getKey(),
                'name' => $this->addedBy->name,
            ],
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
