<?php

namespace App\Http\Requests\Inventory;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

final class StoreHouseholdProductRequest extends FormRequest
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
            'product_uuid' => 'required|uuid',
            'low_stock_threshold' => 'required|string|max:20',
        ];
    }
}
