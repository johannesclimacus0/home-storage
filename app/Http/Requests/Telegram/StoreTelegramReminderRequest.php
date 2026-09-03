<?php

namespace App\Http\Requests\Telegram;

use App\Enums\TelegramReminderFrequency;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class StoreTelegramReminderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'message' => 'required|string|max:1000',
            'remind_at' => 'required|date|after:now',
            'frequency' => ['nullable', 'string', Rule::enum(TelegramReminderFrequency::class)],
        ];
    }
}
