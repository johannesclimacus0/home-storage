<?php

namespace App\Actions\Recipes;

use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Recipes\DeleteRecipeData;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class DeleteRecipeAction
{
    public function __construct(private RecipeRepository $repository) {}

    public function handle(DeleteRecipeData $data): void
    {
        DB::transaction(function () use ($data): void {
            $recipe = $this->repository->findByUuidForUpdate($data->recipeUuid);

            if ($recipe->created_by_user_id === null
                || $recipe->created_by_user_id !== $data->actorUserId) {
                throw new AuthorizationException;
            }

            $this->repository->delete($recipe);
        });
    }
}
