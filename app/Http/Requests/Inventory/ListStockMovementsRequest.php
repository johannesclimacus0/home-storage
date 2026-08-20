<?php

namespace App\Http\Requests\Inventory;

use App\Enums\StockMovementType;
use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ListStockMovementsRequest extends FormRequest
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
            'product_uuid' => 'nullable|uuid',
            'type' => ['nullable', Rule::enum(StockMovementType::class)],
            'per_page' => 'nullable|integer|min:1|max:100',
        ];
    }
}
