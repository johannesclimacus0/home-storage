<?php

namespace App\Actions\Notes;

use App\Contracts\Notes\RecipeNoteRepository;
use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Notes\UpdateRecipeNoteData;
use App\Models\RecipeNote;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

final readonly class UpdateRecipeNoteAction
{
    public function __construct(
        private RecipeRepository $recipes,
        private RecipeNoteRepository $notes
    ) {}

    public function handle(UpdateRecipeNoteData $data): RecipeNote
    {
        $content = trim($data->content);

        if ($content === '') {
            throw new InvalidArgumentException('Note content cannot be empty.');
        }

        return DB::transaction(function () use ($data, $content): RecipeNote {
            $recipe = $this->recipes->findByUuidForUpdate($data->recipeUuid);
            $author = User::query()->findOrFail($data->actorUserId);
            $note = $this->notes->findForRecipeAndAuthorForUpdate(
                $recipe,
                $author,
                $data->noteUuid
            );

            $this->notes->update($note, $content);

            return $note->refresh();
        });
    }
}
