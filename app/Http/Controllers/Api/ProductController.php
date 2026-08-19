<?php

namespace App\Http\Controllers\Api;

use App\Actions\Products\CreateProductAction;
use App\Actions\Products\ListProductsAction;
use App\Actions\Products\ShowProductAction;
use App\DTO\Products\CreateProductData;
use App\Enums\MeasurementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Products\ListProductsRequest;
use App\Http\Requests\Products\StoreProductRequest;
use App\Http\Requests\Products\ViewProductRequest;
use App\Http\Resources\ProductResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Gate;

final class ProductController extends Controller
{
    public function index(
        ListProductsRequest $request,
        ListProductsAction $action,
    ): AnonymousResourceCollection {
        return ProductResource::collection($action->handle());
    }

    public function store(
        StoreProductRequest $request,
        CreateProductAction $action,
    ): JsonResponse {
        $product = $action->handle(new CreateProductData(
            name: $request->validated('name'),
            measurementType: MeasurementType::from($request->validated('measurement_type')),
        ));

        return (new ProductResource($product))
            ->response()
            ->setStatusCode(201);
    }

    public function show(
        ViewProductRequest $request,
        string $product,
        ShowProductAction $action,
    ): ProductResource {
        $model = $action->handle($product);

        Gate::authorize('view', $model);

        return new ProductResource($model);
    }
}
