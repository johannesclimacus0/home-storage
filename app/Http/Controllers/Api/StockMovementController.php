<?php

namespace App\Http\Controllers\Api;

use App\Actions\Inventory\ListStockMovementsAction;
use App\DTO\Inventory\ListStockMovementsData;
use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ListStockMovementsRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\Household;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class StockMovementController extends Controller
{
    public function index(
        ListStockMovementsRequest $request,
        Household $household,
        ListStockMovementsAction $action
    ): AnonymousResourceCollection {
        $type = $request->validated('type');

        $movements = $action->handle(new ListStockMovementsData(
            householdUuid: $household->uuid,
            actorUserId: $request->user()->getKey(),
            productUuid: $request->validated('product_uuid'),
            type: $type === null ? null : StockMovementType::from($type),
            perPage: (int) $request->validated('per_page', 20),
        ));

        return StockMovementResource::collection($movements);
    }
}
