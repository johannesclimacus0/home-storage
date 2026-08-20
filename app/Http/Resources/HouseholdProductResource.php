<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class HouseholdProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->product->uuid,
            'name' => $this->product->name,
            'measurement_type' => $this->product->measurement_type->value,
            'low_stock_threshold' => $this->low_stock_threshold,
            'total_quantity' => (string) ($this->stocks_sum_quantity ?? '0.000'),
            'is_low_stock' => $this->low_stock_since !== null,
            'low_stock_since' => $this->low_stock_since?->toISOString(),
        ];
    }
}
