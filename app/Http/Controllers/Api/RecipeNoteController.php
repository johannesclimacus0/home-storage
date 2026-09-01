<?php

namespace App\Http\Controllers\Api;

use App\Actions\Notes\CreateRecipeNoteAction;
use App\Actions\Notes\DeleteRecipeNoteAction;
use App\Actions\Notes\ListRecipeNotesAction;
use App\Actions\Notes\ShowRecipeNoteAction;
use App\Actions\Notes\UpdateRecipeNoteAction;
use App\DTO\Notes\CreateRecipeNoteData;
use App\DTO\Notes\DeleteRecipeNoteData;
use App\DTO\Notes\UpdateRecipeNoteData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notes\ListRecipeNotesRequest;
use App\Http\Requests\Notes\StoreRecipeNoteRequest;
use App\Http\Requests\Notes\UpdateRecipeNoteRequest;
use App\Http\Requests\Recipes\ViewRecipeRequest;
use App\Http\Resources\RecipeNoteResource;
use App\Models\Recipe;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class RecipeNoteController extends Controller
{
    public function index(
        ListRecipeNotesRequest $request,
        Recipe $recipe,
        ListRecipeNotesAction $action
    ): AnonymousResourceCollection {
        return RecipeNoteResource::collection($action->handle(
            $recipe->uuid,
            $request->user()->getKey(),
            $request->integer('per_page', 10)
        ));
    }

    public function store(
        StoreRecipeNoteRequest $request,
        Recipe $recipe,
        CreateRecipeNoteAction $action
    ): JsonResponse {
        $resource = new RecipeNoteResource($action->handle(
            new CreateRecipeNoteData(
                recipeUuid: $recipe->uuid,
                actorUserId: $request->user()->getKey(),
                content: $request->validated('content')
            )
        ));

        return $resource->response()->setStatusCode(201);
    }

    public function show(
        ViewRecipeRequest $request,
        Recipe $recipe,
        string $recipeNote,
        ShowRecipeNoteAction $action
    ): RecipeNoteResource {
        return new RecipeNoteResource($action->handle(
            $recipe->uuid,
            $request->user()->getKey(),
            $recipeNote
        ));
    }

    public function update(
        UpdateRecipeNoteRequest $request,
        Recipe $recipe,
        string $recipeNote,
        UpdateRecipeNoteAction $action
    ): RecipeNoteResource {
        return new RecipeNoteResource($action->handle(
            new UpdateRecipeNoteData(
                recipeUuid: $recipe->uuid,
                actorUserId: $request->user()->getKey(),
                noteUuid: $recipeNote,
                content: $request->validated('content')
            )
        ));
    }

    public function destroy(
        ViewRecipeRequest $request,
        Recipe $recipe,
        string $recipeNote,
        DeleteRecipeNoteAction $action
    ): Response {
        $action->handle(new DeleteRecipeNoteData(
            recipeUuid: $recipe->uuid,
            actorUserId: $request->user()->getKey(),
            noteUuid: $recipeNote
        ));

        return response()->noContent();
    }
}
