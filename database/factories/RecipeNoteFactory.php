<?php

namespace Database\Factories;

use App\Models\Recipe;
use App\Models\RecipeNote;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<RecipeNote> */
class RecipeNoteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'author_id' => User::factory(),
            'content' => $this->faker->text(),
        ];
    }
}
