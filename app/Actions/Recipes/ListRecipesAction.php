<?php

namespace App\Actions\Recipes;

use App\Contracts\Recipes\RecipeRepository;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ListRecipesAction
{
    public function __construct(private RecipeRepository $repository)
    {
    }

    public function handle(int $perPage): LengthAwarePaginator
    {

        return $this->repository->paginate($perPage);
    }
}
