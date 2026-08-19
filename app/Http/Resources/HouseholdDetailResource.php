<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class HouseholdDetailResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $membership = $this->householdMemberships
            ->firstWhere('user_id', $request->user()->getKey());

        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'role' => $membership?->role->value,
            'members' => HouseholdMemberResource::collection($this->householdMemberships),
        ];
    }
}
