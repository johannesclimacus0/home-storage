<?php

namespace App\Models;

use App\Traits\HasUuidRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable([
    'household_id',
    'sender_id',
    'content',
    'edited_at',
])]
class HouseholdMessage extends Model
{
    /** @use HasFactory<\Database\Factories\HouseholdMessageFactory> */
    use HasFactory, HasUuidRouteKey, SoftDeletes;

    protected $casts = [
        'edited_at' => 'immutable_datetime',
        'deleted_at' => 'immutable_datetime',
    ];

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function household(): BelongsTo
    {
        return $this->belongsTo(Household::class);
    }
}
