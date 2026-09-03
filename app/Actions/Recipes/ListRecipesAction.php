<?php

namespace App\Actions\Recipes;

use App\Contracts\Recipes\RecipeRepository;
use App\Models\Recipe;
use App\Support\Cache\RecipeCache;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;

final readonly class ListRecipesAction
{
    public function __construct(
        private RecipeRepository $repository,
        private RecipeCache $cache
    ) {}

    /** @return LengthAwarePaginator<int, array<string, mixed>> */
    public function handle(int $perPage): LengthAwarePaginator
    {
        $page = LengthAwarePaginator::resolveCurrentPage();

        return $this->cache->rememberList(
            $perPage,
            $page,
            fn (): LengthAwarePaginator => $this->repository
                ->paginate($perPage)
                ->through(fn (Recipe $recipe): array => [
                    'uuid' => $recipe->uuid,
                    'title' => $recipe->title,
                    'description' => $recipe->description,
                    'image_url' => $recipe->image_path === null
                        ? null
                        : Storage::disk('public')->url($recipe->image_path),
                    'servings' => $recipe->servings,
                    'before_cooking_minutes' => $recipe->before_cooking_minutes,
                    'cooking_minutes' => $recipe->cooking_minutes,
                    'creator' => $recipe->creator === null ? null : [
                        'id' => $recipe->creator->getKey(),
                        'name' => $recipe->creator->name,
                    ],
                    'ingredients_count' => $recipe->ingredients_count,
                    'steps_count' => $recipe->steps_count,
                ])
        );
    }
}
