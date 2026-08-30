<?php

namespace App\Http\Requests\Recipes;

use App\Enums\MeasurementUnit;
use App\Models\Recipe;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreRecipeIngredientRequest extends FormRequest
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
            'product_uuid' => 'required|uuid',
            'quantity' => 'required|string|max:20',
            'unit' => ['required', Rule::enum(MeasurementUnit::class)],
            'is_optional' => 'sometimes|boolean',
            'note' => 'nullable|string|max:255',
        ];
    }
}
