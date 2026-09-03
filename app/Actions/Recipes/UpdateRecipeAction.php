<?php

namespace App\Actions\Recipes;

use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Recipes\UpdateRecipeData;
use App\Models\Recipe;
use App\Services\Recipes\RecipeImageStorage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class UpdateRecipeAction
{
    public function __construct(
        private RecipeRepository $repository,
        private RecipeImageStorage $images
    ) {}

    public function handle(UpdateRecipeData $data): Recipe
    {
        $newImagePath = null;
        $oldImagePath = null;

        try {
            $recipe = DB::transaction(function () use (
                $data,
                &$newImagePath,
                &$oldImagePath
            ): Recipe {
                $recipe = $this->repository->findByUuidForUpdate($data->recipeUuid);

                if ($recipe->created_by_user_id === null
                    || $recipe->created_by_user_id !== $data->actorUserId) {
                    throw new AuthorizationException;
                }

                $oldImagePath = $recipe->image_path;
                $newImagePath = $data->removeImage || $data->image === null
                    ? null
                    : $this->images->store($data->image);
                $imagePath = $data->removeImage
                    ? null
                    : ($newImagePath ?? $oldImagePath);

                $this->repository->update(
                    recipe: $recipe,
                    title: $data->title,
                    description: $data->description,
                    servings: $data->servings,
                    beforeCookingMinutes: $data->beforeCookingMinutes,
                    cookingMinutes: $data->cookingMinutes,
                    imagePath: $imagePath
                );

                return $recipe->refresh();
            });
        } catch (Throwable $exception) {
            $this->images->delete($newImagePath);

            throw $exception;
        }

        if ($newImagePath !== null || $data->removeImage) {
            $this->images->delete($oldImagePath);
        }

        return $recipe;
    }
}
