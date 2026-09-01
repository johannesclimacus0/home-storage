<?php

namespace App\Http\Requests\Recipes;

use App\Enums\RecipeAvailabilityFilter;
use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListHouseholdRecipesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');

        return $household instanceof Household
            && $this->user()->can('view', $household);
    }

    public function rules(): array
    {
        return [
            'availability' => ['nullable', Rule::enum(RecipeAvailabilityFilter::class)],
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
