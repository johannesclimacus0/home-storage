<?php

namespace App\Repositories;

use App\Contracts\Inventory\StockMovementRepository;
use App\DTO\Inventory\CreateStockMovementData;
use App\Enums\StockMovementType;
use App\Models\Household;
use App\Models\StockMovement;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class EloquentStockMovementRepository implements StockMovementRepository
{
    public function create(CreateStockMovementData $data): StockMovement
    {
        return StockMovement::query()->create([
            'household_id' => $data->householdId,
            'household_product_id' => $data->householdProductId,
            'product_id' => $data->productId,
            'storage_location_id' => $data->storageLocationId,
            'actor_user_id' => $data->actorUserId,
            'type' => $data->type,
            'input_quantity' => $data->inputQuantity,
            'input_unit' => $data->inputUnit,
            'quantity_delta' => $data->quantityDelta,
            'quantity_before' => $data->quantityBefore,
            'quantity_after' => $data->quantityAfter,
            'product_name' => $data->productName,
            'storage_location_name' => $data->storageLocationName,
            'actor_name' => $data->actorName,
        ]);
    }

    public function paginateForHousehold(
        Household $household,
        ?string $productUuid,
        ?StockMovementType $type,
        int $perPage,
    ): LengthAwarePaginator {
        return StockMovement::query()
            ->with(['product', 'storageLocation', 'actor'])
            ->where('household_id', $household->id)
            ->when(
                $productUuid !== null,
                fn (Builder $query) => $query->whereHas(
                    'product',
                    fn (Builder $productQuery) => $productQuery
                        ->where('uuid', $productUuid),
                ),
            )
            ->when(
                $type !== null,
                fn (Builder $query) => $query
                    ->where('type', $type->value),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }
}
