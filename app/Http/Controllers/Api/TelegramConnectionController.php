<?php

namespace App\Http\Controllers\Api;

use App\Actions\Telegram\DisconnectTelegramAccountAction;
use App\Actions\Users\UpdateUserTimezoneAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Users\UpdateUserTimezoneRequest;
use DateTimeZone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

final class TelegramConnectionController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $connection = $request->user()
            ->telegramConnection()
            ->with('chat')
            ->first();

        return response()->json([
            'connected' => $connection !== null,
            'linked_at' => $connection?->linked_at?->toIso8601String(),
            'chat_name' => $connection?->chat->name,
            'timezone' => $request->user()->timezone,
            'timezones' => DateTimeZone::listIdentifiers(),
        ]);
    }

    public function updateTimezone(
        UpdateUserTimezoneRequest $request,
        UpdateUserTimezoneAction $action
    ): JsonResponse {
        $user = $action->handle($request->user(), $request->validated('timezone'));

        return response()->json(['timezone' => $user->timezone]);
    }

    public function destroy(Request $request, DisconnectTelegramAccountAction $action): Response
    {
        $user = $request->user();

        $action->handle($user);

        return response()->noContent();
    }
}
