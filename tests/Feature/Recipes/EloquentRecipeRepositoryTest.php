<?php

namespace Tests\Feature\Recipes;

use App\Contracts\Recipes\RecipeRepository;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EloquentRecipeRepositoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_repository_paginates_recipes_in_title_order_with_summary_relations(): void
    {
        Recipe::factory()->create(['title' => 'Soup']);
        $first = Recipe::factory()->create(['title' => 'Bread']);
        RecipeIngredient::factory()->for($first)->create();
        RecipeStep::factory()->for($first)->create();

        $paginator = $this->repository()->paginate(1);
        $recipe = $paginator->items()[0];

        $this->assertSame(2, $paginator->total());
        $this->assertSame(1, $paginator->perPage());
        $this->assertSame($first->getKey(), $recipe->getKey());
        $this->assertTrue($recipe->relationLoaded('creator'));
        $this->assertSame(1, $recipe->ingredients_count);
        $this->assertSame(1, $recipe->steps_count);
    }

    public function test_repository_finds_recipe_with_ingredients_products_and_steps(): void
    {
        $recipe = Recipe::factory()->create();
        RecipeIngredient::factory()->for($recipe)->create();
        RecipeStep::factory()->for($recipe)->create();

        $found = $this->repository()->findByUuid($recipe->uuid);

        $this->assertTrue($found->relationLoaded('creator'));
        $this->assertTrue($found->relationLoaded('ingredients'));
        $this->assertTrue($found->ingredients->first()->relationLoaded('product'));
        $this->assertTrue($found->relationLoaded('steps'));
    }

    public function test_repository_creates_updates_and_deletes_recipe(): void
    {
        $creator = User::factory()->create();
        $repository = $this->repository();

        $recipe = $repository->create(
            $creator,
            'Test recipe',
            'Test description',
            2,
            10,
            30
        );

        $this->assertTrue($recipe->creator->is($creator));

        $repository->update(
            $recipe,
            'Updated recipe',
            null,
            4,
            15,
            45
        );

        $this->assertDatabaseHas('recipes', [
            'id' => $recipe->getKey(),
            'title' => 'Updated recipe',
            'description' => null,
            'servings' => 4,
            'before_cooking_minutes' => 15,
            'cooking_minutes' => 45,
        ]);

        $repository->delete($recipe);

        $this->assertDatabaseMissing('recipes', ['id' => $recipe->getKey()]);
    }

    public function test_repository_manages_ingredients_scoped_to_recipe(): void
    {
        $recipe = Recipe::factory()->create();
        $otherRecipe = Recipe::factory()->create();
        $product = Product::factory()->create();
        $replacement = Product::factory()->create();
        $repository = $this->repository();

        $ingredient = $repository->createIngredient(
            $recipe,
            $product,
            '350.000',
            1,
            false,
            null
        );

        $this->assertTrue($repository->hasProduct($recipe, $product));
        $this->assertFalse($repository->hasProduct($recipe, $product, $ingredient));
        $this->assertTrue(
            $repository->findIngredientForUpdate($recipe, $ingredient->uuid)->is($ingredient)
        );

        $repository->updateIngredient(
            $ingredient,
            $replacement,
            '500.000',
            1,
            true,
            'Test note'
        );

        $this->assertDatabaseHas('recipe_ingredients', [
            'id' => $ingredient->getKey(),
            'product_id' => $replacement->getKey(),
            'quantity' => '500.000',
            'is_optional' => true,
            'note' => 'Test note',
        ]);

        try {
            $repository->findIngredientForUpdate($otherRecipe, $ingredient->uuid);
            $this->fail('Foreign recipe ingredient was found.');
        } catch (ModelNotFoundException) {
            $this->addToAssertionCount(1);
        }

        $repository->deleteIngredient($ingredient);

        $this->assertDatabaseMissing('recipe_ingredients', ['id' => $ingredient->getKey()]);
    }

    public function test_repository_manages_steps_scoped_to_recipe(): void
    {
        $recipe = Recipe::factory()->create();
        $otherRecipe = Recipe::factory()->create();
        $repository = $this->repository();

        $step = $repository->createStep($recipe, 1, 'First instruction');

        $this->assertTrue(
            $repository->findStepForUpdate($recipe, $step->uuid)->is($step)
        );

        $repository->updateStep($step, 1, 'Updated instruction');

        $this->assertDatabaseHas('recipe_steps', [
            'id' => $step->getKey(),
            'position' => 1,
            'description' => 'Updated instruction',
        ]);

        try {
            $repository->findStepForUpdate($otherRecipe, $step->uuid);
            $this->fail('Foreign recipe step was found.');
        } catch (ModelNotFoundException) {
            $this->addToAssertionCount(1);
        }

        $repository->deleteStep($step);

        $this->assertDatabaseMissing('recipe_steps', ['id' => $step->getKey()]);
    }

    private function repository(): RecipeRepository
    {
        return app(RecipeRepository::class);
    }
}
