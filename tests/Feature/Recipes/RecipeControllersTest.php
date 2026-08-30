<?php

namespace Tests\Feature\Recipes;

use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeControllersTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_list_and_view_recipes(): void
    {
        $user = User::factory()->create();
        Recipe::factory()->create(['title' => 'Soup']);
        $recipe = Recipe::factory()->create(['title' => 'Bread']);
        $ingredient = RecipeIngredient::factory()->for($recipe)->create();
        $step = RecipeStep::factory()->for($recipe)->create();

        $this->actingAs($user)
            ->getJson('/api/recipes?per_page=1')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonPath('data.0.uuid', $recipe->uuid)
            ->assertJsonPath('data.0.title', 'Bread')
            ->assertJsonPath('data.0.ingredients_count', 1)
            ->assertJsonPath('data.0.steps_count', 1)
            ->assertJsonMissingPath('data.0.ingredients')
            ->assertJsonMissingPath('data.0.steps');

        $this->actingAs($user)
            ->getJson("/api/recipes/{$recipe->uuid}")
            ->assertOk()
            ->assertJsonPath('data.uuid', $recipe->uuid)
            ->assertJsonPath('data.ingredients.0.uuid', $ingredient->uuid)
            ->assertJsonPath('data.ingredients.0.product.uuid', $ingredient->product->uuid)
            ->assertJsonPath('data.steps.0.uuid', $step->uuid);
    }

    public function test_verified_user_can_create_update_and_delete_own_recipe(): void
    {
        $user = User::factory()->create();

        $createResponse = $this->actingAs($user)->postJson('/api/recipes', [
            'title' => '  Test   recipe  ',
            'description' => 'Test description',
            'servings' => 2,
            'before_cooking_minutes' => 10,
            'cooking_minutes' => 30,
        ]);

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.title', 'Test recipe')
            ->assertJsonPath('data.servings', 2);

        $recipe = Recipe::query()
            ->where('uuid', $createResponse->json('data.uuid'))
            ->firstOrFail();

        $this->assertTrue($recipe->creator->is($user));

        $this->actingAs($user)
            ->putJson("/api/recipes/{$recipe->uuid}", [
                'title' => 'Updated recipe',
                'description' => null,
                'servings' => 4,
                'before_cooking_minutes' => 15,
                'cooking_minutes' => 45,
            ])
            ->assertOk()
            ->assertJsonPath('data.title', 'Updated recipe')
            ->assertJsonPath('data.servings', 4)
            ->assertJsonPath('data.description', null);

        $this->assertDatabaseHas('recipes', [
            'id' => $recipe->getKey(),
            'title' => 'Updated recipe',
            'servings' => 4,
        ]);

        $this->actingAs($user)
            ->deleteJson("/api/recipes/{$recipe->uuid}")
            ->assertNoContent();

        $this->assertDatabaseMissing('recipes', ['id' => $recipe->getKey()]);
    }

    public function test_recipe_request_validation_is_returned_as_json(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson('/api/recipes', [
                'title' => '',
                'servings' => 0,
                'before_cooking_minutes' => -1,
                'cooking_minutes' => -1,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'servings',
                'before_cooking_minutes',
                'cooking_minutes',
            ]);

        $this->assertDatabaseCount('recipes', 0);
    }

    public function test_user_cannot_update_or_delete_another_users_recipe(): void
    {
        $recipe = Recipe::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->putJson("/api/recipes/{$recipe->uuid}", [
                'title' => 'Foreign update',
                'description' => null,
                'servings' => 2,
                'before_cooking_minutes' => 10,
                'cooking_minutes' => 20,
            ])
            ->assertForbidden();

        $this->actingAs($outsider)
            ->deleteJson("/api/recipes/{$recipe->uuid}")
            ->assertForbidden();

        $this->assertDatabaseHas('recipes', [
            'id' => $recipe->getKey(),
            'title' => $recipe->title,
        ]);
    }

    public function test_recipe_owner_can_create_update_and_delete_ingredient(): void
    {
        $recipe = Recipe::factory()->create();
        $product = Product::factory()->mass()->create();
        $owner = $recipe->creator;

        $createResponse = $this->actingAs($owner)->postJson(
            "/api/recipes/{$recipe->uuid}/ingredients",
            [
                'product_uuid' => $product->uuid,
                'quantity' => '0.5',
                'unit' => 'kg',
                'note' => ' Test note ',
            ]
        );

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.product.uuid', $product->uuid)
            ->assertJsonPath('data.quantity', '500.000')
            ->assertJsonPath('data.position', 1)
            ->assertJsonPath('data.is_optional', false)
            ->assertJsonPath('data.note', 'Test note');

        $ingredient = RecipeIngredient::query()
            ->where('uuid', $createResponse->json('data.uuid'))
            ->firstOrFail();

        $this->actingAs($owner)
            ->putJson("/api/recipes/{$recipe->uuid}/ingredients/{$ingredient->uuid}", [
                'product_uuid' => $product->uuid,
                'quantity' => '250',
                'unit' => 'g',
                'is_optional' => true,
                'note' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.quantity', '250.000')
            ->assertJsonPath('data.is_optional', true)
            ->assertJsonPath('data.note', null);

        $this->actingAs($owner)
            ->deleteJson("/api/recipes/{$recipe->uuid}/ingredients/{$ingredient->uuid}")
            ->assertNoContent();

        $this->assertDatabaseMissing('recipe_ingredients', [
            'id' => $ingredient->getKey(),
        ]);
    }

    public function test_recipe_owner_can_create_update_and_delete_step(): void
    {
        $recipe = Recipe::factory()->create();
        $owner = $recipe->creator;

        $createResponse = $this->actingAs($owner)->postJson(
            "/api/recipes/{$recipe->uuid}/steps",
            ['description' => ' First step ']
        );

        $createResponse
            ->assertCreated()
            ->assertJsonPath('data.position', 1)
            ->assertJsonPath('data.description', 'First step');

        $step = RecipeStep::query()
            ->where('uuid', $createResponse->json('data.uuid'))
            ->firstOrFail();

        $this->actingAs($owner)
            ->putJson("/api/recipes/{$recipe->uuid}/steps/{$step->uuid}", [
                'description' => 'Updated step',
            ])
            ->assertOk()
            ->assertJsonPath('data.uuid', $step->uuid)
            ->assertJsonPath('data.description', 'Updated step');

        $this->actingAs($owner)
            ->deleteJson("/api/recipes/{$recipe->uuid}/steps/{$step->uuid}")
            ->assertNoContent();

        $this->assertDatabaseMissing('recipe_steps', ['id' => $step->getKey()]);
    }

    public function test_nested_recipe_resources_are_scoped_to_parent_recipe(): void
    {
        $owner = User::factory()->create();
        $recipe = Recipe::factory()->for($owner, 'creator')->create();
        $otherRecipe = Recipe::factory()->for($owner, 'creator')->create();
        $ingredient = RecipeIngredient::factory()->for($otherRecipe)->create();
        $step = RecipeStep::factory()->for($otherRecipe)->create();

        $this->actingAs($owner)
            ->deleteJson("/api/recipes/{$recipe->uuid}/ingredients/{$ingredient->uuid}")
            ->assertNotFound();

        $this->actingAs($owner)
            ->deleteJson("/api/recipes/{$recipe->uuid}/steps/{$step->uuid}")
            ->assertNotFound();

        $this->assertDatabaseHas('recipe_ingredients', ['id' => $ingredient->getKey()]);
        $this->assertDatabaseHas('recipe_steps', ['id' => $step->getKey()]);
    }

    public function test_guest_cannot_access_recipe_api(): void
    {
        $recipe = Recipe::factory()->create();

        $this->getJson('/api/recipes')->assertUnauthorized();
        $this->getJson("/api/recipes/{$recipe->uuid}")->assertUnauthorized();
    }
}
