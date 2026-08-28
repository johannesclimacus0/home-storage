<?php

namespace App\Http\Requests\Messages;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

final class ListMessagesRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');

        return $household instanceof Household
            && $this->user()->can('view', $household);
    }

    public function rules(): array
    {
        return [
            'per_page' => 'nullable|integer|min:1|max:100',
            'cursor' => 'nullable|string'
        ];
    }
}
