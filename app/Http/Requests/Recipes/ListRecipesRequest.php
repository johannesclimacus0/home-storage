<?php

namespace App\Http\Requests\Recipes;

use App\Models\Recipe;
use Illuminate\Foundation\Http\FormRequest;

final class ListRecipesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', Recipe::class);
    }

    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
