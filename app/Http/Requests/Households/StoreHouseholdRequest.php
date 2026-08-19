<?php

namespace App\Http\Requests\Households;

use Illuminate\Foundation\Http\FormRequest;

final class StoreHouseholdRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'required|string|min:3|max:255',
        ];
    }
}
