<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateHouseholdProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'low_stock_threshold' => 'required|string', 'max:20',
        ];
    }
}
