<?php

namespace App\Http\Requests\Households;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

final class StoreHouseholdMemberRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');

        return $household instanceof Household
            && $this->user()->can('addMember', $household);
    }

    public function rules(): array
    {
        return [
            'email' => 'required|string|email|max:255',
        ];
    }
}
