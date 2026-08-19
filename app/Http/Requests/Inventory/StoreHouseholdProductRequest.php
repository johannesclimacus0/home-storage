<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

final class StoreHouseholdProductRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'product_uuid' => 'required|uuid',
            'low_stock_threshold' => 'required|string|max:20',
        ];
    }
}
