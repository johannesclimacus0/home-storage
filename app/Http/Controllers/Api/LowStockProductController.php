<?php

namespace App\Http\Controllers\Api;

use App\Actions\Inventory\ListLowStockProductsAction;
use App\DTO\Inventory\ListLowStockProductsData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\ListLowStockProductsRequest;
use App\Http\Resources\HouseholdProductResource;
use App\Models\Household;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final class LowStockProductController extends Controller
{
    public function index(
        ListLowStockProductsRequest $request,
        Household $household,
        ListLowStockProductsAction $action,
    ): AnonymousResourceCollection {
        return HouseholdProductResource::collection($action->handle(
            new ListLowStockProductsData(
                householdUuid: $household->uuid,
                actorUserId: $request->user()->getKey(),
            ),
        ));
    }
}
