<?php

namespace App\Http\Controllers\Api;

use App\Actions\Notifications\ListNotificationsAction;
use App\Actions\Notifications\MarkAllNotificationsAsReadAction;
use App\Actions\Notifications\MarkNotificationAsReadAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Notifications\ListNotificationsRequest;
use App\Http\Resources\NotificationResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class NotificationController extends Controller
{
    public function index(
        ListNotificationsRequest $request,
        ListNotificationsAction $action
    ): AnonymousResourceCollection {
        $notifications = $action->handle(
            user: $request->user(),
            perPage: $request->integer('per_page', 15),
        );

        return NotificationResource::collection($notifications);
    }

    public function markAllAsRead(
        Request $request,
        MarkAllNotificationsAsReadAction $action
    ): JsonResponse {
        $updatedCount = $action->handle($request->user());

        return response()->json([
            'updated_count' => $updatedCount,
        ]);
    }

    public function markAsRead(
        Request $request,
        MarkNotificationAsReadAction $action,
        string $notification
    ): NotificationResource {
        $result = $action->handle(
            $request->user(),
            $notification,
        );

        return new NotificationResource($result);
    }
}
