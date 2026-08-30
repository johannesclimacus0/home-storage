<?php

namespace App\Http\Requests\Recipes;

use App\Models\Recipe;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateRecipeStepRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recipe = $this->route('recipe');

        return $recipe instanceof Recipe
            && $this->user()->can('update', $recipe);
    }

    public function rules(): array
    {
        return [
            'description' => 'required|string|max:5000',
        ];
    }
}
