<?php

namespace App\Actions\Recipes;

use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Recipes\CreateRecipeData;
use App\Models\Recipe;
use App\Models\User;

final readonly class CreateRecipeAction
{
    public function __construct(private RecipeRepository $repository)
    {
    }

    public function handle(CreateRecipeData $data): Recipe
    {
        return $this->repository->create(
            creator: User::query()->findOrFail($data->actorUserId),
            title: $data->title,
            description: $data->description,
            servings: $data->servings,
            beforeCookingMinutes: $data->beforeCookingMinutes,
            cookingMinutes: $data->cookingMinutes,
        );
    }
}
