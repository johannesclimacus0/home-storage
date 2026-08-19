<?php

namespace App\Http\Requests\Households;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

final class TransferHouseholdOwnershipRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');

        return $household instanceof Household
            && $this->user()->can('transferOwnership', $household);
    }

    public function rules(): array
    {
        return [
            'new_owner_user_id' => 'required|integer',
        ];
    }
}
