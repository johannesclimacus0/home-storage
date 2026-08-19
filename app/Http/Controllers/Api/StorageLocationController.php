<?php

namespace App\Http\Controllers\Api;

use App\Actions\Inventory\CreateStorageLocationAction;
use App\DTO\Inventory\CreateStorageLocationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreStorageLocationRequest;
use App\Http\Resources\StorageLocationResource;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StorageLocationController extends Controller
{
    public function store(
        StoreStorageLocationRequest $request,
        string $household,
        CreateStorageLocationAction $action,
    ): JsonResponse {
        $data = new CreateStorageLocationData(
            householdUuid: $household,
            actorUserId: $request->user()->getKey(),
            name: $request->validated('name'),
        );

        $result = $action->handle($data);

        return new StorageLocationResource($result)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
