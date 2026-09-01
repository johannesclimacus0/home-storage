<?php

namespace App\Http\Requests\Notes;

use App\Models\Recipe;
use Illuminate\Foundation\Http\FormRequest;

final class ListRecipeNotesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recipe = $this->route('recipe');

        return $recipe instanceof Recipe
            && $this->user()->can('view', $recipe);
    }

    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
