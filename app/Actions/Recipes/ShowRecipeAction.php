<?php

namespace App\Actions\Recipes;

use App\Contracts\Recipes\RecipeRepository;
use App\Models\Recipe;

final readonly class ShowRecipeAction
{
    public function __construct(private RecipeRepository $repository)
    {
    }

    public function handle(string $recipeUuid):Recipe
    {
        return $this->repository->findByUuid($recipeUuid);
    }
}
