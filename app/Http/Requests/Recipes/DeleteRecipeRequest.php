<?php

namespace App\Http\Requests\Recipes;

use App\Models\Recipe;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recipe = $this->route('recipe');

        return $recipe instanceof Recipe
            && $this->user()->can('delete', $recipe);
    }

    public function rules(): array
    {
        return [];
    }
}
