<?php

namespace App\Http\Controllers\Api;

use App\Actions\Recipes\CreateRecipeAction;
use App\Actions\Recipes\DeleteRecipeAction;
use App\Actions\Recipes\ListRecipesAction;
use App\Actions\Recipes\ShowRecipeAction;
use App\Actions\Recipes\UpdateRecipeAction;
use App\DTO\Recipes\CreateRecipeData;
use App\DTO\Recipes\DeleteRecipeData;
use App\DTO\Recipes\UpdateRecipeData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recipes\DeleteRecipeRequest;
use App\Http\Requests\Recipes\ListRecipesRequest;
use App\Http\Requests\Recipes\StoreRecipeRequest;
use App\Http\Requests\Recipes\UpdateRecipeRequest;
use App\Http\Requests\Recipes\ViewRecipeRequest;
use App\Http\Resources\RecipeListResource;
use App\Http\Resources\RecipeResource;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class RecipeController extends Controller
{
    public function index(
        ListRecipesRequest $request,
        ListRecipesAction $action
    ): AnonymousResourceCollection {
        return RecipeListResource::collection(
            $action->handle((int) $request->validated('per_page', 8))
        );
    }

    public function store(
        StoreRecipeRequest $request,
        CreateRecipeAction $action
    ): JsonResponse {
        $recipe = $action->handle(new CreateRecipeData(
            actorUserId: $request->user()->getKey(),
            title: $request->validated('title'),
            description: $request->validated('description'),
            servings: $request->integer('servings'),
            beforeCookingMinutes: $request->integer('before_cooking_minutes'),
            cookingMinutes: $request->integer('cooking_minutes'),
            image: $request->file('image')
        ));

        return new RecipeResource($recipe)
            ->response()
            ->setStatusCode(201);
    }

    public function show(
        ViewRecipeRequest $request,
        Recipe $recipe,
        ShowRecipeAction $action
    ): RecipeResource {
        return new RecipeResource($action->handle($recipe->uuid));
    }

    public function update(
        UpdateRecipeRequest $request,
        Recipe $recipe,
        UpdateRecipeAction $action,
        ShowRecipeAction $showAction
    ): RecipeResource {
        $updatedRecipe = $action->handle(new UpdateRecipeData(
            recipeUuid: $recipe->uuid,
            actorUserId: $request->user()->getKey(),
            title: $request->validated('title'),
            description: $request->validated('description'),
            servings: $request->integer('servings'),
            beforeCookingMinutes: $request->integer('before_cooking_minutes'),
            cookingMinutes: $request->integer('cooking_minutes'),
            image: $request->file('image'),
            removeImage: $request->boolean('remove_image')
        ));

        return new RecipeResource(
            $showAction->handle($updatedRecipe->uuid)
        );
    }

    public function destroy(
        DeleteRecipeRequest $request,
        Recipe $recipe,
        DeleteRecipeAction $action
    ): Response {
        $action->handle(new DeleteRecipeData(
            recipeUuid: $recipe->uuid,
            actorUserId: $request->user()->getKey()
        ));

        return response()->noContent();
    }
}
