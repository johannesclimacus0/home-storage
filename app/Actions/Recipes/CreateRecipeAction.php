<?php

namespace App\Actions\Recipes;

use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Recipes\CreateRecipeData;
use App\Models\Recipe;
use App\Models\User;
use App\Services\Recipes\RecipeImageStorage;
use Illuminate\Support\Facades\DB;
use Throwable;

final readonly class CreateRecipeAction
{
    public function __construct(
        private RecipeRepository $repository,
        private RecipeImageStorage $images
    ) {}

    public function handle(CreateRecipeData $data): Recipe
    {
        $creator = User::query()->findOrFail($data->actorUserId);
        $imagePath = $data->image === null
            ? null
            : $this->images->store($data->image);

        try {
            return DB::transaction(fn (): Recipe => $this->repository->create(
                creator: $creator,
                title: $data->title,
                description: $data->description,
                servings: $data->servings,
                beforeCookingMinutes: $data->beforeCookingMinutes,
                cookingMinutes: $data->cookingMinutes,
                imagePath: $imagePath
            ));
        } catch (Throwable $exception) {
            $this->images->delete($imagePath);

            throw $exception;
        }
    }
}
