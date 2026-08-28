<?php

namespace App\Http\Requests\Shopping;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

final class PurchaseShoppingListItemRequest extends FormRequest
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
            'storage_location_uuid' => ['required', 'uuid'],
        ];
    }
}
