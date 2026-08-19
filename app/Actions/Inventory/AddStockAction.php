<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\DTO\Inventory\AddStockData;
use App\DTO\Inventory\AddStockResult;
use App\Support\Inventory\StockQuantity;
use Illuminate\Support\Facades\DB;

final readonly class AddStockAction
{
    public function __construct(
        private HouseholdRepository $households,
        private InventoryRepository $inventory,
    ) {}

    public function handle(AddStockData $data): AddStockResult
    {
        return DB::transaction(function () use ($data): AddStockResult {
            $household = $this->households->findByUuidForUpdate($data->householdUuid);
            $this->households->findMembershipForUpdate($household, $data->actorUserId);

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
            }

            return new AddStockResult(
                householdUuid: $household->uuid,
                productUuid: $householdProduct->product->uuid,
                storageLocationUuid: $storageLocation->uuid,
                addedQuantity: $quantity,
                unit: $data->unit->baseUnit(),
                locationQuantity: $stock?->quantity ?? '0.000',
                totalQuantity: StockQuantity::databaseValue(
                    $this->inventory->totalStockQuantity($householdProduct),
                ),
            );
        });
    }
}
