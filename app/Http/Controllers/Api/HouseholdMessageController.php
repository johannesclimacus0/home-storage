<?php

namespace App\Http\Controllers\Api;

use App\Actions\Messages\DeleteMessageAction;
use App\Actions\Messages\ListMessagesAction;
use App\Actions\Messages\SendMessageAction;
use App\Actions\Messages\UpdateMessageAction;
use App\DTO\Messages\SendMessageData;
use App\DTO\Messages\UpdateMessageData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Messages\DeleteMessageRequest;
use App\Http\Requests\Messages\ListMessagesRequest;
use App\Http\Requests\Messages\StoreMessageRequest;
use App\Http\Requests\Messages\UpdateMessageRequest;
use App\Http\Resources\HouseholdMessageResource;
use App\Models\Household;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class HouseholdMessageController extends Controller
{
    public function index(
        ListMessagesRequest $request,
        Household $household,
        ListMessagesAction $action
    ): AnonymousResourceCollection {
        return HouseholdMessageResource::collection($action->handle(
            $household->uuid,
            $request->user()->getKey(),
            $request->integer('per_page', 30)
        ));
    }

    public function store(
        StoreMessageRequest $request,
        Household $household,
        SendMessageAction $action
    ): JsonResponse {
        $resource = new HouseholdMessageResource($action->handle(
            new SendMessageData(
                householdUuid: $household->uuid,
                senderId: $request->user()->getKey(),
                content: $request->validated('content')
            )
        ));

        return $resource->response()->setStatusCode(201);
    }

    public function update(
        UpdateMessageRequest $request,
        Household $household,
        string $message,
        UpdateMessageAction $action
    ): HouseholdMessageResource {
        return new HouseholdMessageResource($action->handle(
            new UpdateMessageData(
                householdUuid: $household->uuid,
                actorUserId: $request->user()->getKey(),
                messageUuid: $message,
                content: $request->validated('content')
            )
        ));
    }

    public function destroy(
        DeleteMessageRequest $request,
        Household $household,
        string $message,
        DeleteMessageAction $action
    ): Response {
        $action->handle(
            $household->uuid,
            $request->user()->getKey(),
            $message
        );

        return response()->noContent();
    }
}
