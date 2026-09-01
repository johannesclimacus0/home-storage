<?php

namespace App\Contracts\Notes;

use App\Models\Recipe;
use App\Models\RecipeNote;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface RecipeNoteRepository
{
    public function paginateForRecipeAndAuthor(
        Recipe $recipe,
        User $author,
        int $perPage
    ): LengthAwarePaginator;

    public function findForRecipeAndAuthor(
        Recipe $recipe,
        User $author,
        string $uuid
    ): RecipeNote;

    public function findForRecipeAndAuthorForUpdate(
        Recipe $recipe,
        User $author,
        string $uuid
    ): RecipeNote;

    public function create(
        Recipe $recipe,
        User $author,
        string $content
    ): RecipeNote;

    public function update(RecipeNote $note, string $content): void;

    public function delete(RecipeNote $note): void;
}
