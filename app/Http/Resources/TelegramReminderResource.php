<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class TelegramReminderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'uuid' => $this->uuid,
            'message' => $this->message,
            'remind_at' => $this->remind_at->toIso8601String(),
            'frequency' => $this->frequency?->value,
            'dispatched_at' => $this->dispatched_at?->toIso8601String(),
        ];
    }
}
