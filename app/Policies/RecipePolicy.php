<?php

namespace App\Policies;

use App\Models\Recipe;
use App\Models\User;

class RecipePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Recipe $recipe): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Recipe $recipe): bool
    {
        return $recipe->created_by_user_id !== null
            && $recipe->created_by_user_id === $user->getKey();
    }

    public function delete(User $user, Recipe $recipe): bool
    {
        return $this->update($user, $recipe);
    }
}
