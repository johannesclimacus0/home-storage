<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HouseholdMembershipResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->household->uuid,
            'name' => $this->household->name,
            'role' => $this->role->value,
            'low_stock_reminders_enabled' => $this->low_stock_reminders_enabled,
            'low_stock_reminder_interval_hours' => $this->low_stock_reminder_interval_hours,
        ];
    }
}
