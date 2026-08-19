<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ConsumeStockResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'household_uuid' => $this->householdUuid,
            'product_uuid' => $this->productUuid,
            'storage_location_uuid' => $this->storageLocationUuid,
            'consumed_quantity' => $this->consumedQuantity,
            'unit' => $this->unit->value,
            'location_quantity' => $this->locationQuantity,
            'total_quantity' => $this->totalQuantity,
        ];
    }
}
