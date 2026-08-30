<?php

namespace App\Actions\Recipes;

use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Recipes\DeleteRecipeStepData;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class DeleteRecipeStepAction
{
    public function __construct(private RecipeRepository $repository) {}

    public function handle(DeleteRecipeStepData $data): void
    {
        DB::transaction(function () use ($data): void {
            $recipe = $this->repository->findByUuidForUpdate($data->recipeUuid);

            if ($recipe->created_by_user_id === null
                || $recipe->created_by_user_id !== $data->actorUserId) {
                throw new AuthorizationException;
            }

            $step = $this->repository->findStepForUpdate(
                $recipe,
                $data->stepUuid
            );

            $this->repository->deleteStep($step);
        });
    }
}
