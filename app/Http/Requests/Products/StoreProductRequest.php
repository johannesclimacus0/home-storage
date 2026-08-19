<?php

namespace App\Http\Requests\Products;

use App\Enums\MeasurementType;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

final class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Product::class);
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name') && is_string($this->input('name'))) {
            $this->merge([
                'name' => Str::squish($this->input('name')),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:255',
            'measurement_type' => ['required', Rule::enum(MeasurementType::class)],
        ];
    }
}
