<?php

namespace App\Actions\Recipes;

use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Recipes\AddRecipeStepData;
use App\Models\RecipeStep;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class AddRecipeStepAction
{
    public function __construct(private RecipeRepository $repository) {}

    public function handle(AddRecipeStepData $data): RecipeStep
    {
        return DB::transaction(function () use ($data): RecipeStep {
            $recipe = $this->repository->findByUuidForUpdate($data->recipeUuid);

            if ($recipe->created_by_user_id === null
                || $recipe->created_by_user_id !== $data->actorUserId) {
                throw new AuthorizationException;
            }

            $description = trim($data->description);

            if ($description === '') {
                throw new InvalidArgumentException(__('messages.recipes.step_description_required'));
            }

            return $this->repository->createStep(
                recipe: $recipe,
                position: $this->repository->nextStepPosition($recipe),
                description: $description
            );
        });
    }
}
