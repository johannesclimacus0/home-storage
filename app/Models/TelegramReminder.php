<?php

namespace App\Models;

use App\Enums\TelegramReminderFrequency;
use App\Traits\HasUuidRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'message', 'remind_at', 'frequency', 'dispatched_at'])]
class TelegramReminder extends Model
{
    use HasFactory, HasUuidRouteKey;

    protected $casts = [
        'remind_at' => 'immutable_datetime',
        'frequency' => TelegramReminderFrequency::class,
        'dispatched_at' => 'immutable_datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
