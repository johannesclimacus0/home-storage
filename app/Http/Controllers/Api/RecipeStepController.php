<?php

namespace App\Http\Controllers\Api;

use App\Actions\Recipes\AddRecipeStepAction;
use App\Actions\Recipes\DeleteRecipeStepAction;
use App\Actions\Recipes\UpdateRecipeStepAction;
use App\DTO\Recipes\AddRecipeStepData;
use App\DTO\Recipes\DeleteRecipeStepData;
use App\DTO\Recipes\UpdateRecipeStepData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recipes\DeleteRecipeStepRequest;
use App\Http\Requests\Recipes\StoreRecipeStepRequest;
use App\Http\Requests\Recipes\UpdateRecipeStepRequest;
use App\Http\Resources\RecipeStepResource;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RecipeStepController extends Controller
{
    public function store(
        StoreRecipeStepRequest $request,
        Recipe $recipe,
        AddRecipeStepAction $action
    ): JsonResponse {
        $step = $action->handle(new AddRecipeStepData(
            recipeUuid: $recipe->uuid,
            actorUserId: $request->user()->getKey(),
            description: $request->validated('description')
        ));

        return (new RecipeStepResource($step))
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateRecipeStepRequest $request,
        Recipe $recipe,
        string $step,
        UpdateRecipeStepAction $action
    ): RecipeStepResource {
        $updatedStep = $action->handle(new UpdateRecipeStepData(
            recipeUuid: $recipe->uuid,
            stepUuid: $step,
            actorUserId: $request->user()->getKey(),
            description: $request->validated('description')
        ));

        return new RecipeStepResource($updatedStep);
    }

    public function destroy(
        DeleteRecipeStepRequest $request,
        Recipe $recipe,
        string $step,
        DeleteRecipeStepAction $action
    ): Response {
        $action->handle(new DeleteRecipeStepData(
            recipeUuid: $recipe->uuid,
            stepUuid: $step,
            actorUserId: $request->user()->getKey()
        ));

        return response()->noContent();
    }
}
