<?php

namespace App\Http\Requests\Inventory;

use App\Enums\MeasurementUnit;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class AddStockRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'storage_location_uuid' => 'required|uuid',
            'quantity' => 'required|string|max:20',
            'unit' => ['required', Rule::enum(MeasurementUnit::class)],
        ];
    }
}
