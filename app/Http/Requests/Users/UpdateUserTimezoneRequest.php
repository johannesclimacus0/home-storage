<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

final class UpdateUserTimezoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'timezone' => ['required', 'string', 'timezone:all'],
        ];
    }
}
