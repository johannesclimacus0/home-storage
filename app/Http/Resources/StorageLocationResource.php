<?php

namespace App\Http\Resources;

use App\DTO\Inventory\CreateStorageLocationResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class StorageLocationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        if ($this->resource instanceof CreateStorageLocationResult) {
            return [
                'uuid' => $this->locationUuid,
                'household_uuid' => $this->householdUuid,
                'name' => $this->name,
            ];
        }

        return [
            'uuid' => $this->uuid,
            'household_uuid' => $this->household->uuid,
            'name' => $this->name,
        ];
    }
}
