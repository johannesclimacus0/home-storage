<?php

namespace Tests\Feature\Recipes;

use App\Actions\Recipes\AddRecipeIngredientAction;
use App\Actions\Recipes\AddRecipeStepAction;
use App\Actions\Recipes\DeleteRecipeAction;
use App\Actions\Recipes\DeleteRecipeIngredientAction;
use App\Actions\Recipes\DeleteRecipeStepAction;
use App\Actions\Recipes\UpdateRecipeIngredientAction;
use App\Actions\Recipes\UpdateRecipeStepAction;
use App\DTO\Recipes\AddRecipeIngredientData;
use App\DTO\Recipes\AddRecipeStepData;
use App\DTO\Recipes\DeleteRecipeData;
use App\DTO\Recipes\DeleteRecipeIngredientData;
use App\DTO\Recipes\DeleteRecipeStepData;
use App\DTO\Recipes\UpdateRecipeIngredientData;
use App\DTO\Recipes\UpdateRecipeStepData;
use App\Enums\MeasurementUnit;
use App\Exceptions\Recipes\RecipeIngredientConflict;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\RecipeStep;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_delete_recipe_with_ingredients_and_steps(): void
    {
        $recipe = Recipe::factory()->create();
        $ingredient = RecipeIngredient::factory()->for($recipe)->create();
        $step = RecipeStep::factory()->for($recipe)->create();

        app(DeleteRecipeAction::class)->handle(new DeleteRecipeData(
            recipeUuid: $recipe->uuid,
            actorUserId: $recipe->created_by_user_id
        ));

        $this->assertDatabaseMissing('recipes', ['id' => $recipe->getKey()]);
        $this->assertDatabaseMissing('recipe_ingredients', ['id' => $ingredient->getKey()]);
        $this->assertDatabaseMissing('recipe_steps', ['id' => $step->getKey()]);
    }

    public function test_non_owner_cannot_delete_recipe(): void
    {
        $recipe = Recipe::factory()->create();
        $outsider = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(DeleteRecipeAction::class)->handle(new DeleteRecipeData(
            recipeUuid: $recipe->uuid,
            actorUserId: $outsider->getKey()
        ));
    }

    public function test_owner_can_add_ingredients_with_converted_quantity_and_next_position(): void
    {
        $recipe = Recipe::factory()->create();
        $flour = Product::factory()->mass()->create();
        $rice = Product::factory()->mass()->create();
        $action = app(AddRecipeIngredientAction::class);

        $first = $action->handle(new AddRecipeIngredientData(
            recipeUuid: $recipe->uuid,
            actorUserId: $recipe->created_by_user_id,
            productUuid: $flour->uuid,
            quantity: '0.5',
            unit: MeasurementUnit::Kilogram,
            isOptional: false,
            note: '  Add somethings somewhere dont ask me  '
        ));
        $second = $action->handle(new AddRecipeIngredientData(
            recipeUuid: $recipe->uuid,
            actorUserId: $recipe->created_by_user_id,
            productUuid: $rice->uuid,
            quantity: '350',
            unit: MeasurementUnit::Gram,
            isOptional: true,
            note: null
        ));

        $this->assertSame('500.000', $first->quantity);
        $this->assertSame(1, $first->position);
        $this->assertSame('Add somethings somewhere dont ask me', $first->note);
        $this->assertTrue($first->relationLoaded('product'));
        $this->assertSame('350.000', $second->quantity);
        $this->assertSame(2, $second->position);
        $this->assertTrue($second->is_optional);
    }

    public function test_recipe_cannot_contain_same_product_twice(): void
    {
        $recipe = Recipe::factory()->create();
        $product = Product::factory()->mass()->create();
        $action = app(AddRecipeIngredientAction::class);
        $data = new AddRecipeIngredientData(
            recipeUuid: $recipe->uuid,
            actorUserId: $recipe->created_by_user_id,
            productUuid: $product->uuid,
            quantity: '100',
            unit: MeasurementUnit::Gram,
            isOptional: false,
            note: null
        );

        $action->handle($data);

        $this->expectException(RecipeIngredientConflict::class);

        $action->handle($data);
    }

    public function test_owner_can_update_and_delete_ingredient(): void
    {
        $recipe = Recipe::factory()->create();
        $product = Product::factory()->volume()->create();
        $ingredient = RecipeIngredient::factory()
            ->for($recipe)
            ->for($product)
            ->create(['position' => 3]);

        $updated = app(UpdateRecipeIngredientAction::class)->handle(
            new UpdateRecipeIngredientData(
                recipeUuid: $recipe->uuid,
                ingredientUuid: $ingredient->uuid,
                actorUserId: $recipe->created_by_user_id,
                productUuid: $product->uuid,
                quantity: '1.5',
                unit: MeasurementUnit::Liter,
                isOptional: true,
                note: ' Updated note '
            )
        );

        $this->assertSame('1500.000', $updated->quantity);
        $this->assertSame(3, $updated->position);
        $this->assertTrue($updated->is_optional);
        $this->assertSame('Updated note', $updated->note);

        app(DeleteRecipeIngredientAction::class)->handle(
            new DeleteRecipeIngredientData(
                recipeUuid: $recipe->uuid,
                ingredientUuid: $ingredient->uuid,
                actorUserId: $recipe->created_by_user_id
            )
        );

        $this->assertDatabaseMissing('recipe_ingredients', [
            'id' => $ingredient->getKey(),
        ]);
    }

    public function test_non_owner_cannot_add_ingredient(): void
    {
        $recipe = Recipe::factory()->create();
        $outsider = User::factory()->create();
        $product = Product::factory()->mass()->create();

        $this->expectException(AuthorizationException::class);

        app(AddRecipeIngredientAction::class)->handle(
            new AddRecipeIngredientData(
                recipeUuid: $recipe->uuid,
                actorUserId: $outsider->getKey(),
                productUuid: $product->uuid,
                quantity: '100',
                unit: MeasurementUnit::Gram,
                isOptional: false,
                note: null
            )
        );
    }

    public function test_owner_can_add_update_and_delete_steps(): void
    {
        $recipe = Recipe::factory()->create();
        $add = app(AddRecipeStepAction::class);

        $first = $add->handle(new AddRecipeStepData(
            recipeUuid: $recipe->uuid,
            actorUserId: $recipe->created_by_user_id,
            description: '  Add something to already existing something i dont care  '
        ));
        $second = $add->handle(new AddRecipeStepData(
            recipeUuid: $recipe->uuid,
            actorUserId: $recipe->created_by_user_id,
            description: 'Bake'
        ));

        $this->assertSame(1, $first->position);
        $this->assertSame('Add something to already existing something i dont care', $first->description);
        $this->assertSame(2, $second->position);

        $updated = app(UpdateRecipeStepAction::class)->handle(
            new UpdateRecipeStepData(
                recipeUuid: $recipe->uuid,
                stepUuid: $first->uuid,
                actorUserId: $recipe->created_by_user_id,
                description: '  Updated  '
            )
        );

        $this->assertSame(1, $updated->position);
        $this->assertSame('Updated', $updated->description);

        app(DeleteRecipeStepAction::class)->handle(new DeleteRecipeStepData(
            recipeUuid: $recipe->uuid,
            stepUuid: $first->uuid,
            actorUserId: $recipe->created_by_user_id
        ));

        $this->assertDatabaseMissing('recipe_steps', ['id' => $first->getKey()]);
    }

    public function test_non_owner_cannot_add_step(): void
    {
        $recipe = Recipe::factory()->create();
        $outsider = User::factory()->create();

        $this->expectException(AuthorizationException::class);

        app(AddRecipeStepAction::class)->handle(new AddRecipeStepData(
            recipeUuid: $recipe->uuid,
            actorUserId: $outsider->getKey(),
            description: 'Test step'
        ));
    }
}
