<?php

namespace App\Http\Requests\Telegram;

use App\Enums\TelegramNotificationType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class UpdateTelegramSubscriptionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subscriptions' => 'present|array',
            'subscriptions.*' => ['required', 'string', Rule::enum(TelegramNotificationType::class)],
        ];
    }
}
