<?php

namespace App\Models;

use App\Traits\HasUuidRouteKey;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'recipe_id',
    'author_id',
    'content',
])]
class RecipeNote extends Model
{
    /** @use HasFactory<\Database\Factories\RecipeNoteFactory> */
    use HasFactory, HasUuidRouteKey;

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
