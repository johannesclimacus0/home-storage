<?php

namespace App\Http\Controllers\Api;

use App\Actions\Telegram\UpdateTelegramSubscriptionsAction;
use App\Enums\TelegramNotificationType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\UpdateTelegramSubscriptionsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class TelegramSubscriptionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $enabledTypes = $request->user()
            ->telegramNotificationSubscriptions()
            ->pluck('type')
            ->all();

        return response()->json([
            'data' => collect(TelegramNotificationType::cases())
                ->map(fn (TelegramNotificationType $type): array => [
                    'key' => $type->value,
                    'label' => $type->label(),
                    'enabled' => in_array($type, $enabledTypes, true),
                ])
                ->all(),
        ]);
    }

    public function update(
        UpdateTelegramSubscriptionsRequest $request,
        UpdateTelegramSubscriptionsAction $action
    ): JsonResponse {
        $types = collect($request->validated('subscriptions'))
            ->map(fn (string $type): TelegramNotificationType => TelegramNotificationType::from($type))
            ->all();

        $action->handle($request->user(), $types);

        return $this->index($request);
    }
}
