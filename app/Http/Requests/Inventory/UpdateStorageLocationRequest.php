<?php

namespace App\Http\Requests\Inventory;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateStorageLocationRequest extends FormRequest
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
            'name' => 'required|string|min:3|max:255',
        ];
    }
}
