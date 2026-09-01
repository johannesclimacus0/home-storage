<?php

namespace App\Policies;

use App\Models\RecipeNote;
use App\Models\User;

final class RecipeNotePolicy
{
    public function view(User $user, RecipeNote $note): bool
    {
        return $note->author_id === $user->getKey();
    }

    public function update(User $user, RecipeNote $note): bool
    {
        return $this->view($user, $note);
    }

    public function delete(User $user, RecipeNote $note): bool
    {
        return $this->view($user, $note);
    }
}
