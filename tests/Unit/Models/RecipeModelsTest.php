<?php

namespace Tests\Unit\Models;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class RecipeModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_recipe_has_expected_relationships_and_casts(): void
    {
        $recipe = Recipe::factory()->create([
            'servings' => 4,
            'before_cooking_minutes' => 15,
            'cooking_minutes' => 45,
        ]);

        $this->assertInstanceOf(User::class, $recipe->creator);
        $this->assertTrue($recipe->creator->recipes->contains($recipe));
        $this->assertTrue(Str::isUuid($recipe->uuid));
        $this->assertSame(4, $recipe->servings);
        $this->assertSame(15, $recipe->before_cooking_minutes);
        $this->assertSame(45, $recipe->cooking_minutes);
    }

    public function test_recipe_ingredients_have_expected_relationships_casts_and_order(): void
    {
        $recipe = Recipe::factory()->create();

        $second = RecipeIngredient::factory()->for($recipe)->optional()->create([
            'quantity' => 350,
            'position' => 2,
        ]);
        $first = RecipeIngredient::factory()->for($recipe)->create([
            'quantity' => 500,
            'position' => 1,
        ]);

        $this->assertInstanceOf(Recipe::class, $first->recipe);
        $this->assertInstanceOf(Product::class, $first->product);
        $this->assertTrue($first->product->recipeIngredients->contains($first));
        $this->assertTrue(Str::isUuid($first->uuid));
        $this->assertSame('500.000', $first->quantity);
        $this->assertSame(1, $first->position);
        $this->assertFalse($first->is_optional);
        $this->assertTrue($second->is_optional);
        $this->assertSame([$first->id, $second->id], $recipe->ingredients->pluck('id')->all());
    }

    public function test_recipe_steps_belong_to_recipe_and_are_ordered(): void
    {
        $recipe = Recipe::factory()->create();

        $second = RecipeStep::factory()->for($recipe)->create(['position' => 2]);
        $first = RecipeStep::factory()->for($recipe)->create(['position' => 1]);

        $this->assertInstanceOf(Recipe::class, $first->recipe);
        $this->assertTrue(Str::isUuid($first->uuid));
        $this->assertSame(1, $first->position);
        $this->assertSame([$first->id, $second->id], $recipe->steps->pluck('id')->all());
    }

    public function test_system_recipe_has_no_creator_and_user_deletion_keeps_recipe(): void
    {
        $systemRecipe = Recipe::factory()->system()->create();
        $recipe = Recipe::factory()->create();
        $creator = $recipe->creator;

        $this->assertNull($systemRecipe->creator);

        $creator->delete();

        $this->assertNull($recipe->refresh()->created_by_user_id);
        $this->assertDatabaseHas('recipes', ['id' => $recipe->id]);
    }
}
