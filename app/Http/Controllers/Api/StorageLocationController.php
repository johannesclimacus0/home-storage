<?php

namespace App\Http\Controllers\Api;

use App\Actions\Inventory\CreateStorageLocationAction;
use App\DTO\Inventory\CreateStorageLocationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreStorageLocationRequest;
use App\Http\Resources\StorageLocationResource;
use App\Models\Household;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class StorageLocationController extends Controller
{
    public function store(
        StoreStorageLocationRequest $request,
        Household $household,
        CreateStorageLocationAction $action,
    ): JsonResponse {
        $data = new CreateStorageLocationData(
            householdUuid: $household->uuid,
            actorUserId: $request->user()->getKey(),
            name: $request->validated('name'),
        );

        $result = $action->handle($data);

        return new StorageLocationResource($result)
            ->response()
            ->setStatusCode(Response::HTTP_CREATED);
    }
}
