<?php

namespace App\Http\Requests\Households;

use App\Models\Household;
use Illuminate\Foundation\Http\FormRequest;

final class LeaveHouseholdRequest extends FormRequest
{
    public function authorize(): bool
    {
        $household = $this->route('household');

        return $household instanceof Household
            && $this->user()->can('leave', $household);
    }

    public function rules(): array
    {
        return [];
    }
}
