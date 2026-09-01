<?php

namespace App\Actions\Notes;

use App\Contracts\Notes\RecipeNoteRepository;
use App\Contracts\Recipes\RecipeRepository;
use App\DTO\Notes\DeleteRecipeNoteData;
use App\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class DeleteRecipeNoteAction
{
    public function __construct(
        private RecipeRepository $recipes,
        private RecipeNoteRepository $notes
    ) {}

    public function handle(DeleteRecipeNoteData $data): void
    {
        DB::transaction(function () use ($data): void {
            $recipe = $this->recipes->findByUuidForUpdate($data->recipeUuid);
            $author = User::query()->findOrFail($data->actorUserId);
            $note = $this->notes->findForRecipeAndAuthorForUpdate(
                $recipe,
                $author,
                $data->noteUuid
            );

            $this->notes->delete($note);
        });
    }
}
