<?php

namespace App\Http\Requests\Shopping;

use App\Enums\MeasurementUnit;
use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateShoppingListItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');

        return $household instanceof Household
            && $this->user()->can('manageInventory', $household);
    }

    public function rules(): array
    {
        return [
            'quantity' => 'required|string|max:20',
            'unit' => ['required', Rule::enum(MeasurementUnit::class)],
        ];
    }
}
