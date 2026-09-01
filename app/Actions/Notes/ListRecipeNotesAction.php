<?php

namespace App\Actions\Notes;

use App\Contracts\Notes\RecipeNoteRepository;
use App\Contracts\Recipes\RecipeRepository;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final readonly class ListRecipeNotesAction
{
    public function __construct(
        private RecipeRepository $recipes,
        private RecipeNoteRepository $notes
    ) {}

    public function handle(
        string $recipeUuid,
        int $actorUserId,
        int $perPage
    ): LengthAwarePaginator {
        $recipe = $this->recipes->findByUuid($recipeUuid);
        $author = User::query()->findOrFail($actorUserId);

        return $this->notes->paginateForRecipeAndAuthor($recipe, $author, $perPage);
    }
}
