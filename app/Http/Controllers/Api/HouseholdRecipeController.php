<?php

namespace App\Http\Controllers\Api;

use App\Actions\Recipes\AddMissingRecipeIngredientsToShoppingListAction;
use App\Actions\Recipes\ListHouseholdRecipesAction;
use App\Actions\Recipes\ShowRecipeAvailabilityAction;
use App\Enums\RecipeAvailabilityFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Recipes\ListHouseholdRecipesRequest;
use App\Http\Requests\Shopping\ManageShoppingListRequest;
use App\Http\Resources\HouseholdRecipeResource;
use App\Http\Resources\RecipeAvailabilityResource;
use App\Http\Resources\ShoppingListItemResource;
use App\Models\Household;
use App\Models\Recipe;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class HouseholdRecipeController extends Controller
{
    public function index(
        ListHouseholdRecipesRequest $request,
        Household $household,
        ListHouseholdRecipesAction $action
    ): AnonymousResourceCollection {
        return HouseholdRecipeResource::collection($action->handle(
            householdUuid: $household->uuid,
            actorUserId: $request->user()->getKey(),
            filter: RecipeAvailabilityFilter::from(
                $request->validated('availability', RecipeAvailabilityFilter::All->value)
            ),
            perPage: $request->integer('per_page', 12)
        ));
    }

    public function show(
        ManageShoppingListRequest $request,
        Household $household,
        Recipe $recipe,
        ShowRecipeAvailabilityAction $action
    ): RecipeAvailabilityResource {
        return new RecipeAvailabilityResource($action->handle(
            $household->uuid,
            $request->user()->getKey(),
            $recipe->uuid
        ));
    }

    public function addMissingToShoppingList(
        ManageShoppingListRequest $request,
        Household $household,
        Recipe $recipe,
        AddMissingRecipeIngredientsToShoppingListAction $action
    ): AnonymousResourceCollection {
        return ShoppingListItemResource::collection($action->handle(
            $household->uuid,
            $request->user()->getKey(),
            $recipe->uuid
        ));
    }
}
