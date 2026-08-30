<?php

namespace App\Http\Controllers\Api;

use App\Actions\Recipes\AddRecipeIngredientAction;
use App\Actions\Recipes\DeleteRecipeIngredientAction;
use App\Actions\Recipes\UpdateRecipeIngredientAction;
use App\DTO\Recipes\AddRecipeIngredientData;
use App\DTO\Recipes\DeleteRecipeIngredientData;
use App\DTO\Recipes\UpdateRecipeIngredientData;
use App\Enums\MeasurementUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recipes\DeleteRecipeIngredientRequest;
use App\Http\Requests\Recipes\StoreRecipeIngredientRequest;
use App\Http\Requests\Recipes\UpdateRecipeIngredientRequest;
use App\Http\Resources\RecipeIngredientResource;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RecipeIngredientController extends Controller
{
    public function store(
        StoreRecipeIngredientRequest $request,
        Recipe $recipe,
        AddRecipeIngredientAction $action
    ): JsonResponse {
        $ingredient = $action->handle(new AddRecipeIngredientData(
            recipeUuid: $recipe->uuid,
            actorUserId: $request->user()->getKey(),
            productUuid: $request->validated('product_uuid'),
            quantity: $request->validated('quantity'),
            unit: MeasurementUnit::from($request->validated('unit')),
            isOptional: $request->boolean('is_optional'),
            note: $request->validated('note')
        ));

        return new RecipeIngredientResource($ingredient)
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateRecipeIngredientRequest $request,
        Recipe $recipe,
        string $ingredient,
        UpdateRecipeIngredientAction $action
    ): RecipeIngredientResource {
        $updatedIngredient = $action->handle(new UpdateRecipeIngredientData(
            recipeUuid: $recipe->uuid,
            ingredientUuid: $ingredient,
            actorUserId: $request->user()->getKey(),
            productUuid: $request->validated('product_uuid'),
            quantity: $request->validated('quantity'),
            unit: MeasurementUnit::from($request->validated('unit')),
            isOptional: $request->boolean('is_optional'),
            note: $request->validated('note')
        ));

        return new RecipeIngredientResource($updatedIngredient);
    }

    public function destroy(
        DeleteRecipeIngredientRequest $request,
        Recipe $recipe,
        string $ingredient,
        DeleteRecipeIngredientAction $action
    ): Response {
        $action->handle(new DeleteRecipeIngredientData(
            recipeUuid: $recipe->uuid,
            ingredientUuid: $ingredient,
            actorUserId: $request->user()->getKey()
        ));

        return response()->noContent();
    }
}
