<?php

namespace App\Http\Controllers\Api;

use App\Actions\Telegram\CreateTelegramReminderAction;
use App\Actions\Telegram\DeleteTelegramReminderAction;
use App\Actions\Telegram\ListTelegramRemindersAction;
use App\Actions\Telegram\UpdateTelegramReminderAction;
use App\Enums\TelegramReminderFrequency;
use App\Http\Controllers\Controller;
use App\Http\Requests\Telegram\StoreTelegramReminderRequest;
use App\Http\Requests\Telegram\UpdateTelegramReminderRequest;
use App\Http\Resources\TelegramReminderResource;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class TelegramReminderController extends Controller
{
    public function index(
        Request $request,
        ListTelegramRemindersAction $action
    ): AnonymousResourceCollection {
        return TelegramReminderResource::collection($action->handle($request->user()));
    }

    public function store(
        StoreTelegramReminderRequest $request,
        CreateTelegramReminderAction $action
    ): JsonResponse {
        $reminder = $action->handle(
            user: $request->user(),
            message: $request->validated('message'),
            remindAt: CarbonImmutable::parse($request->validated('remind_at')),
            frequency: $request->filled('frequency')
                ? TelegramReminderFrequency::from($request->validated('frequency'))
                : null
        );

        return new TelegramReminderResource($reminder)
            ->response()
            ->setStatusCode(201);
    }

    public function update(
        UpdateTelegramReminderRequest $request,
        string $reminder,
        UpdateTelegramReminderAction $action
    ): TelegramReminderResource {
        return new TelegramReminderResource($action->handle(
            user: $request->user(),
            reminderUuid: $reminder,
            message: $request->validated('message'),
            remindAt: CarbonImmutable::parse($request->validated('remind_at')),
            frequency: $request->filled('frequency')
                ? TelegramReminderFrequency::from($request->validated('frequency'))
                : null
        ));
    }

    public function destroy(
        Request $request,
        string $reminder,
        DeleteTelegramReminderAction $action
    ): Response {
        $action->handle($request->user(), $reminder);

        return response()->noContent();
    }
}
