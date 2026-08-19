<?php

namespace App\Http\Controllers\Api;

use App\Actions\Inventory\CreateStorageLocationAction;
use App\Actions\Inventory\DeleteStorageLocationAction;
use App\Actions\Inventory\ListStorageLocationsAction;
use App\Actions\Inventory\ShowStorageLocationAction;
use App\Actions\Inventory\UpdateStorageLocationAction;
use App\DTO\Inventory\CreateStorageLocationData;
use App\DTO\Inventory\UpdateStorageLocationData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ManageHouseholdInventoryRequest;
use App\Http\Requests\Inventory\StoreStorageLocationRequest;
use App\Http\Requests\Inventory\UpdateStorageLocationRequest;
use App\Http\Resources\StorageLocationResource;
use App\Models\Household;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response as HttpResponse;
use Symfony\Component\HttpFoundation\Response;

final class StorageLocationController extends Controller
{
    public function index(
        ManageHouseholdInventoryRequest $request,
        Household $household,
        ListStorageLocationsAction $action
    ): AnonymousResourceCollection {
        return StorageLocationResource::collection(
            $action->handle($household->uuid, $request->user()->getKey()),
        );
    }

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

    public function show(
        ManageHouseholdInventoryRequest $request,
        Household $household,
        string $storageLocation,
        ShowStorageLocationAction $action,
    ): StorageLocationResource {
        return new StorageLocationResource($action->handle(
            $household->uuid,
            $request->user()->getKey(),
            $storageLocation,
        ));
    }

    public function update(
        UpdateStorageLocationRequest $request,
        Household $household,
        string $storageLocation,
        UpdateStorageLocationAction $action,
    ): StorageLocationResource {
        return new StorageLocationResource($action->handle(
            new UpdateStorageLocationData(
                householdUuid: $household->uuid,
                actorUserId: $request->user()->getKey(),
                locationUuid: $storageLocation,
                name: $request->validated('name'),
            ),
        ));
    }

    public function destroy(
        ManageHouseholdInventoryRequest $request,
        Household $household,
        string $storageLocation,
        DeleteStorageLocationAction $action,
    ): HttpResponse {
        $action->handle(
            $household->uuid,
            $request->user()->getKey(),
            $storageLocation,
        );

        return response()->noContent();
    }
}
