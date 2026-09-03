<?php

namespace App\Actions\Recipes;

use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Recipes\DeleteRecipeData;
use App\Services\Recipes\RecipeImageStorage;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

final readonly class DeleteRecipeAction
{
    public function __construct(
        private RecipeRepository $repository,
        private RecipeImageStorage $images
    ) {}

    public function handle(DeleteRecipeData $data): void
    {
        $imagePath = DB::transaction(function () use ($data): ?string {
            $recipe = $this->repository->findByUuidForUpdate($data->recipeUuid);

            if ($recipe->created_by_user_id === null
                || $recipe->created_by_user_id !== $data->actorUserId) {
                throw new AuthorizationException;
            }

            $imagePath = $recipe->image_path;
            $this->repository->delete($recipe);

            return $imagePath;
        });

        $this->images->delete($imagePath);
    }
}
