<?php

namespace App\Http\Requests\Inventory;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateLowStockReminderSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('household'));
    }

    public function rules(): array
    {
        return [
            'enabled' => 'required|boolean',
            'interval_hours' => 'required|integer|min:1|max:720',
        ];
    }
}
