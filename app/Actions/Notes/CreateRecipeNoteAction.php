<?php

namespace App\Actions\Notes;

use App\Contracts\Notes\RecipeNoteRepository;
use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Notes\CreateRecipeNoteData;
use App\Models\RecipeNote;
use App\Models\User;
use InvalidArgumentException;

final readonly class CreateRecipeNoteAction
{
    public function __construct(
        private RecipeRepository $recipes,
        private RecipeNoteRepository $notes
    ) {}

    public function handle(CreateRecipeNoteData $data): RecipeNote
    {
        $content = trim($data->content);

        if ($content === '') {
            throw new InvalidArgumentException('Note content cannot be empty.');
        }

        $recipe = $this->recipes->findByUuid($data->recipeUuid);
        $author = User::query()->findOrFail($data->actorUserId);

        return $this->notes->create($recipe, $author, $content);
    }
}
