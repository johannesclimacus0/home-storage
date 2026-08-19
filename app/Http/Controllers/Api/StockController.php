<?php

namespace App\Http\Controllers\Api;

use App\Actions\Inventory\AddStockAction;
use App\Actions\Inventory\ConsumeStockAction;
use App\DTO\Inventory\AddStockData;
use App\DTO\Inventory\ConsumeStockData;
use App\Enums\MeasurementUnit;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\AddStockRequest;
use App\Http\Requests\Inventory\ConsumeStockRequest;
use App\Http\Resources\AddStockResource;
use App\Http\Resources\ConsumeStockResource;
use App\Models\Household;

final class StockController extends Controller
{
    public function store(
        AddStockRequest $request,
        Household $household,
        string $product,
        AddStockAction $action,
    ): AddStockResource {
        $result = $action->handle(new AddStockData(
            householdUuid: $household->uuid,
            actorUserId: $request->user()->getKey(),
            productUuid: $product,
            storageLocationUuid: $request->validated('storage_location_uuid'),
            quantity: $request->validated('quantity'),
            unit: MeasurementUnit::from($request->validated('unit')),
        ));

        return new AddStockResource($result);
    }

    public function consume(
        ConsumeStockRequest $request,
        Household $household,
        string $product,
        ConsumeStockAction $action,
    ): ConsumeStockResource {
        $result = $action->handle(new ConsumeStockData(
            householdUuid: $household->uuid,
            actorUserId: $request->user()->getKey(),
            productUuid: $product,
            storageLocationUuid: $request->validated('storage_location_uuid'),
            quantity: $request->validated('quantity'),
            unit: MeasurementUnit::from($request->validated('unit')),
        ));

        return new ConsumeStockResource($result);
    }
}
