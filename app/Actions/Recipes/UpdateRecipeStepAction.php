<?php

namespace App\Actions\Recipes;

use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Recipes\UpdateRecipeStepData;
use App\Models\RecipeStep;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateRecipeStepAction
{
    public function __construct(private RecipeRepository $repository) {}

    public function handle(UpdateRecipeStepData $data): RecipeStep
    {
        return DB::transaction(function () use ($data): RecipeStep {
            $recipe = $this->repository->findByUuidForUpdate($data->recipeUuid);

            if ($recipe->created_by_user_id === null
                || $recipe->created_by_user_id !== $data->actorUserId) {
                throw new AuthorizationException;
            }

            $step = $this->repository->findStepForUpdate(
                $recipe,
                $data->stepUuid
            );
            $description = trim($data->description);

            if ($description === '') {
                throw new InvalidArgumentException(__('messages.recipes.step_description_required'));
            }

            $this->repository->updateStep(
                step: $step,
                position: $step->position,
                description: $description
            );

            return $step->refresh();
        });
    }
}
