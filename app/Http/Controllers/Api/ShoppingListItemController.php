<?php

namespace App\Http\Controllers\Api;

use App\Actions\Shopping\AddShoppingListItemAction;
use App\Actions\Shopping\CompleteShoppingListItemAction;
use App\Actions\Shopping\DeleteShoppingListItemAction;
use App\Actions\Shopping\ListShoppingListItemsAction;
use App\Actions\Shopping\PurchaseShoppingListItemAction;
use App\Actions\Shopping\ReopenShoppingListItemAction;
use App\Actions\Shopping\UpdateShoppingListItemAction;
use App\DTO\Shopping\AddShoppingListItemData;
use App\DTO\Shopping\PurchaseShoppingListItemData;
use App\DTO\Shopping\UpdateShoppingListItemData;
use App\Enums\MeasurementUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Shopping\ManageShoppingListRequest;
use App\Http\Requests\Shopping\PurchaseShoppingListItemRequest;
use App\Http\Requests\Shopping\StoreShoppingListItemRequest;
use App\Http\Requests\Shopping\UpdateShoppingListItemRequest;
use App\Http\Resources\ShoppingListItemResource;
use App\Models\Household;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class ShoppingListItemController extends Controller
{
    public function index(
        ManageShoppingListRequest $request,
        Household $household,
        ListShoppingListItemsAction $action
    ): AnonymousResourceCollection {
        return ShoppingListItemResource::collection(
            $action->handle($household->uuid, $request->user()->getKey())
        );
    }

    public function store(
        StoreShoppingListItemRequest $request,
        Household $household,
        AddShoppingListItemAction $action
    ): JsonResponse {
        $resource = new ShoppingListItemResource($action->handle(
            new AddShoppingListItemData(
                householdUuid: $household->uuid,
                actorUserId: $request->user()->getKey(),
                productUuid: $request->validated('product_uuid'),
                quantity: $request->validated('quantity'),
                unit: MeasurementUnit::from($request->validated('unit'))
            )
        ));

        return $resource->response()->setStatusCode(201);
    }

    public function update(
        UpdateShoppingListItemRequest $request,
        Household $household,
        string $shoppingListItem,
        UpdateShoppingListItemAction $action
    ): ShoppingListItemResource {
        return new ShoppingListItemResource($action->handle(
            new UpdateShoppingListItemData(
                householdUuid: $household->uuid,
                actorUserId: $request->user()->getKey(),
                itemUuid: $shoppingListItem,
                quantity: $request->validated('quantity'),
                unit: MeasurementUnit::from($request->validated('unit'))
            )
        ));
    }

    public function complete(
        ManageShoppingListRequest $request,
        Household $household,
        string $shoppingListItem,
        CompleteShoppingListItemAction $action
    ): ShoppingListItemResource {
        return new ShoppingListItemResource($action->handle(
            $household->uuid,
            $request->user()->getKey(),
            $shoppingListItem
        ));
    }

    public function reopen(
        ManageShoppingListRequest $request,
        Household $household,
        string $shoppingListItem,
        ReopenShoppingListItemAction $action
    ): ShoppingListItemResource {
        return new ShoppingListItemResource($action->handle(
            $household->uuid,
            $request->user()->getKey(),
            $shoppingListItem
        ));
    }

    public function purchase(
        PurchaseShoppingListItemRequest $request,
        Household $household,
        string $shoppingListItem,
        PurchaseShoppingListItemAction $action
    ): ShoppingListItemResource {
        return new ShoppingListItemResource($action->handle(
            new PurchaseShoppingListItemData(
                householdUuid: $household->uuid,
                actorUserId: $request->user()->getKey(),
                itemUuid: $shoppingListItem,
                storageLocationUuid: $request->validated('storage_location_uuid')
            )
        ));
    }

    public function destroy(
        ManageShoppingListRequest $request,
        Household $household,
        string $shoppingListItem,
        DeleteShoppingListItemAction $action
    ): Response {
        $action->handle(
            $household->uuid,
            $request->user()->getKey(),
            $shoppingListItem
        );

        return response()->noContent();
    }
}
