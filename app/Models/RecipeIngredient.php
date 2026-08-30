<?php

namespace App\Models;

use App\Traits\HasUuidRouteKey;
use Database\Factories\RecipeIngredientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property-read \App\Models\Product|null $product
 * @property-read \App\Models\Recipe|null $recipe
 * @method static \Database\Factories\RecipeIngredientFactory factory($count = null, $state = [])
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|RecipeIngredient query()
 * @mixin \Eloquent
 */
#[Fillable([
    'recipe_id',
    'product_id',
    'quantity',
    'position',
    'is_optional',
    'note',
])]
class RecipeIngredient extends Model
{
    /** @use HasFactory<RecipeIngredientFactory> */
    use HasFactory, HasUuidRouteKey;

    protected $casts = [
        'quantity' => 'decimal:3',
        'position' => 'integer',
        'is_optional' => 'boolean',
    ];

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
