<?php

namespace App\Actions\Recipes;

use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Recipes\UpdateRecipeData;
use App\Models\Recipe;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class UpdateRecipeAction
{
    public function __construct(private RecipeRepository $repository)
    {
    }

    public function handle(UpdateRecipeData $data): Recipe
    {
        return DB::transaction(function () use ($data): Recipe {
            $recipe = $this->repository->findByUuidForUpdate($data->recipeUuid);

            if ($recipe->created_by_user_id === null
                || $recipe->created_by_user_id !== $data->actorUserId) {
                throw new AuthorizationException;
            }

            $this->repository->update(
                recipe: $recipe,
                title: $data->title,
                description: $data->description,
                servings: $data->servings,
                beforeCookingMinutes: $data->beforeCookingMinutes,
                cookingMinutes: $data->cookingMinutes,
            );
            return $recipe->refresh();
        });
    }
}
