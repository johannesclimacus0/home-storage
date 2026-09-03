<?php

namespace App\Http\Requests\Recipes;

use App\Models\Recipe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

final class UpdateRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $recipe = $this->route('recipe');

        return $recipe instanceof Recipe
            && $this->user()->can('update', $recipe);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('title') && is_string($this->input('title'))) {
            $this->merge([
                'title' => Str::squish($this->input('title')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'title' => 'required|string|min:2|max:255',
            'description' => 'nullable|string|max:10000',
            'servings' => 'required|integer|min:1|max:32767',
            'before_cooking_minutes' => 'required|integer|min:0|max:32767',
            'cooking_minutes' => 'required|integer|min:0|max:32767',
            'image' => 'nullable|prohibited_if:remove_image,true|image|mimes:jpg,jpeg,png,webp|max:5120',
            'remove_image' => 'sometimes|boolean',
        ];
    }
}
