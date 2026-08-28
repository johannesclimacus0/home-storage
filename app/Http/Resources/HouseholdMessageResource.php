<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class HouseholdMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'content' => $this->trashed() ? null : $this->content,
            'sender' => [
                'id' => $this->sender->getKey(),
                'name' => $this->sender->name
            ],
            'is_mine' => $request->user()?->getKey() === $this->sender_id,
            'edited_at' => $this->edited_at?->toISOString(),
            'deleted_at' => $this->deleted_at?->toISOString(),
            'created_at' => $this->created_at?->toISOString()
        ];
    }
}
