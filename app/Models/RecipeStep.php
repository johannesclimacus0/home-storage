<?php

namespace App\Models;

use App\Traits\HasUuidRouteKey;
use Database\Factories\RecipeStepFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Recipe|null $recipe
 * @method static \Database\Factories\RecipeStepFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeStep query()
 * @mixin \Eloquent
 */
#[Fillable([
    'recipe_id',
    'position',
    'description',
])]
class RecipeStep extends Model
{
    /** @use HasFactory<RecipeStepFactory> */
    use HasFactory, HasUuidRouteKey;

    protected $casts = [
        'position' => 'integer',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }
}
