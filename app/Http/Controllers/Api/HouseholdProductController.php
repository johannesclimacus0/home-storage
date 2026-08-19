<?php

namespace App\Http\Controllers\Api;

use App\Actions\Inventory\AddProductToHouseholdAction;
use App\Actions\Inventory\ListHouseholdProductsAction;
use App\Actions\Inventory\RemoveProductFromHouseholdAction;
use App\Actions\Inventory\ShowHouseholdProductAction;
use App\Actions\Inventory\UpdateHouseholdProductAction;
use App\DTO\Inventory\AddProductToHouseholdData;
use App\DTO\Inventory\UpdateHouseholdProductData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\StoreHouseholdProductRequest;
use App\Http\Requests\Inventory\UpdateHouseholdProductRequest;
use App\Http\Resources\HouseholdProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

final class HouseholdProductController extends Controller
{
    public function index(
        Request $request,
        string $household,
        ListHouseholdProductsAction $action,
    ): AnonymousResourceCollection {
        return HouseholdProductResource::collection(
            $action->handle($household, $request->user()->getKey()),
        );
    }

    public function store(
        StoreHouseholdProductRequest $request,
        string $household,
        AddProductToHouseholdAction $action,
    ): JsonResponse {
        $result = $action->handle(new AddProductToHouseholdData(
            householdUuid: $household,
            actorUserId: $request->user()->getKey(),
            productUuid: $request->validated('product_uuid'),
            lowStockThreshold: $request->validated('low_stock_threshold'),
        ));

        return response()->json([
            'data' => [
                'uuid' => $result->productUuid,
                'name' => $result->productName,
                'measurement_type' => $result->measurementType->value,
                'low_stock_threshold' => $result->lowStockThreshold,
                'total_quantity' => '0.000',
            ],
        ], 201);
    }

    public function show(
        Request $request,
        string $household,
        string $product,
        ShowHouseholdProductAction $action
    ): HouseholdProductResource {
        return new HouseholdProductResource(
            $action->handle($household, $request->user()->getKey(), $product),
        );
    }

    public function update(
        UpdateHouseholdProductRequest $request,
        string $household,
        string $product,
        UpdateHouseholdProductAction $action,
    ): HouseholdProductResource {
        return new HouseholdProductResource($action->handle(
            new UpdateHouseholdProductData(
                householdUuid: $household,
                actorUserId: $request->user()->getKey(),
                productUuid: $product,
                lowStockThreshold: $request->validated('low_stock_threshold'),
            ),
        ));
    }

    public function destroy(
        Request $request,
        string $household,
        string $product,
        RemoveProductFromHouseholdAction $action
    ): Response {
        $action->handle($household, $request->user()->getKey(), $product);

        return response()->noContent();
    }
}
