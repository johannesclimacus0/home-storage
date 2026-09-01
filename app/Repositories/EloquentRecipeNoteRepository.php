<?php

namespace App\Repositories;

use App\Contracts\Notes\RecipeNoteRepository;
use App\Models\Recipe;
use App\Models\RecipeNote;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

final class EloquentRecipeNoteRepository implements RecipeNoteRepository
{
    public function paginateForRecipeAndAuthor(
        Recipe $recipe,
        User $author,
        int $perPage
    ): LengthAwarePaginator {
        return $recipe->notes()
            ->where('author_id', $author->getKey())
            ->latest('created_at')
            ->latest('id')
            ->paginate($perPage);
    }

    public function findForRecipeAndAuthor(
        Recipe $recipe,
        User $author,
        string $uuid
    ): RecipeNote {
        return $recipe->notes()
            ->where('author_id', $author->getKey())
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function findForRecipeAndAuthorForUpdate(
        Recipe $recipe,
        User $author,
        string $uuid
    ): RecipeNote {
        return $recipe->notes()
            ->where('author_id', $author->getKey())
            ->where('uuid', $uuid)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function create(
        Recipe $recipe,
        User $author,
        string $content
    ): RecipeNote {
        return $recipe->notes()->create([
            'author_id' => $author->getKey(),
            'content' => $content,
        ]);
    }

    public function update(RecipeNote $note, string $content): void
    {
        $note->updateOrFail(['content' => $content]);
    }

    public function delete(RecipeNote $note): void
    {
        $note->deleteOrFail();
    }
}
