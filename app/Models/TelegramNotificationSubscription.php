<?php

namespace App\Models;

use App\Enums\TelegramNotificationType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'type'])]
class TelegramNotificationSubscription extends Model
{
    protected $casts = [
        'type' => TelegramNotificationType::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
