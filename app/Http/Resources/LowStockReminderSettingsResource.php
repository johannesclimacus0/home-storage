<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class LowStockReminderSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'household_uuid' => $this->household->uuid,
            'enabled' => $this->low_stock_reminders_enabled,
            'interval_hours' => $this->low_stock_reminder_interval_hours,
        ];
    }
}
