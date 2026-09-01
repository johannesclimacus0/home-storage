<?php

namespace App\Http\Requests\Notes;

use App\Models\Recipe;
use Illuminate\Foundation\Http\FormRequest;

final class StoreRecipeNoteRequest extends FormRequest
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
            'content' => 'required|string|max:10000',
        ];
    }
}
