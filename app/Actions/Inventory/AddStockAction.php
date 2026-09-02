<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\Contracts\Inventory\StockMovementRepository;
use App\DTO\Inventory\AddStockData;
use App\DTO\Inventory\AddStockResult;
use App\DTO\Inventory\CreateStockMovementData;
use App\Enums\StockMovementType;
use App\Services\Inventory\LowStockTracker;
use App\Support\Cache\HouseholdCache;
use App\Support\Inventory\StockQuantity;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class AddStockAction
{
    public function __construct(
        private HouseholdRepository $households,
        private InventoryRepository $inventory,
        private StockMovementRepository $movements,
        private LowStockTracker $lowStockTracker,
        private HouseholdCache $cache
    ) {}

    public function handle(AddStockData $data): AddStockResult
    {
        return DB::transaction(function () use ($data): AddStockResult {
            $household = $this->households->findByUuidForUpdate($data->householdUuid);
            $membership = $this->households->findMembershipForUpdate($household, $data->actorUserId);

            $householdProduct = $this->inventory->findHouseholdProductForUpdate(
                $household,
                $data->productUuid,
            );
            $storageLocation = $this->inventory->findStorageLocationForUpdate(
                $household,
                $data->storageLocationUuid,
            );
            $quantity = StockQuantity::toBaseUnit(
                $data->quantity,
                $data->unit,
                $householdProduct->product->measurement_type,
            );

            $stock = $this->inventory->findStockForUpdate(
                $householdProduct,
                $storageLocation,
            );
            $quantityBefore = $stock?->quantity ?? '0.000';

            if ($quantity !== '0.000') {
                if ($stock === null) {
                    $stock = $this->inventory->createStock(
                        $householdProduct,
                        $storageLocation,
                        $quantity,
                    );
                } else {
                    $this->inventory->incrementStock($stock, $quantity);
                }

                $this->movements->create(new CreateStockMovementData(
                    householdId: $household->getKey(),
                    householdProductId: $householdProduct->getKey(),
                    productId: $householdProduct->product->getKey(),
                    storageLocationId: $storageLocation->getKey(),
                    actorUserId: $membership->user->getKey(),
                    type: StockMovementType::Purchase,
                    inputQuantity: $data->quantity,
                    inputUnit: $data->unit,
                    quantityDelta: $quantity,
                    quantityBefore: $quantityBefore,
                    quantityAfter: $stock->quantity,
                    productName: $householdProduct->product->name,
                    storageLocationName: $storageLocation->name,
                    actorName: $membership->user->name,
                ));
            }

            $trackingResult = $this->lowStockTracker->refresh(
                $householdProduct,
                CarbonImmutable::now(),
            );

            DB::afterCommit(fn () => $this->cache->forgetInventory($data->householdUuid));

            return new AddStockResult(
                householdUuid: $household->uuid,
                productUuid: $householdProduct->product->uuid,
                storageLocationUuid: $storageLocation->uuid,
                addedQuantity: $quantity,
                unit: $data->unit->baseUnit(),
                locationQuantity: $stock?->quantity ?? '0.000',
                totalQuantity: $trackingResult->totalQuantity,
            );
        });
    }
}
