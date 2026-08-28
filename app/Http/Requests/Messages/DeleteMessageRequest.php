<?php

namespace App\Http\Requests\Messages;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

final class DeleteMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');

        return $household instanceof Household
            && $this->user()->can('view', $household);
    }

    public function rules(): array
    {
        return [];
    }
}
