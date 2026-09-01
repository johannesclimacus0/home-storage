<?php

namespace App\Actions\Notes;

use App\Contracts\Notes\RecipeNoteRepository;
use App\Contracts\Recipes\RecipeRepository;
use App\Models\RecipeNote;
use App\Models\User;

final readonly class ShowRecipeNoteAction
{
    public function __construct(
        private RecipeRepository $recipes,
        private RecipeNoteRepository $notes
    ) {}

    public function handle(
        string $recipeUuid,
        int $actorUserId,
        string $noteUuid
    ): RecipeNote {
        $recipe = $this->recipes->findByUuid($recipeUuid);
        $author = User::query()->findOrFail($actorUserId);

        return $this->notes->findForRecipeAndAuthor($recipe, $author, $noteUuid);
    }
}
