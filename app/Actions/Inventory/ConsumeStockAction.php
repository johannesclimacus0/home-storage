<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\DTO\Inventory\ConsumeStockData;
use App\DTO\Inventory\ConsumeStockResult;
use App\Exceptions\Inventory\InsufficientStock;
use App\Support\Inventory\StockQuantity;
use Illuminate\Support\Facades\DB;

final readonly class ConsumeStockAction
{
    public function __construct(
        private InventoryRepository $inventory,
        private HouseholdRepository $households,
    ) {}

    public function handle(ConsumeStockData $data): ConsumeStockResult
    {
        return DB::transaction(function () use ($data): ConsumeStockResult {
            $household = $this->households->findByUuidForUpdate($data->householdUuid);
            $this->households->findMembershipForUpdate($household, $data->actorUserId);
            $householdProduct = $this->inventory->findHouseholdProductForUpdate($household, $data->productUuid);
            $location = $this->inventory->findStorageLocationForUpdate($household, $data->storageLocationUuid);

            $quantity = StockQuantity::toBaseUnit(
                $data->quantity,
                $data->unit,
                $householdProduct->product->measurement_type,
            );

            $stock = $this->inventory->findStockForUpdateOrFail($householdProduct, $location);

            if (StockQuantity::toMinorUnits($quantity) > StockQuantity::toMinorUnits($stock->quantity)) {
                throw new InsufficientStock('Insufficient stock.');
            }

            if ($quantity !== '0.000') {
                $this->inventory->decrementStock($stock, $quantity);
            }

            return new ConsumeStockResult(
                householdUuid: $household->uuid,
                productUuid: $householdProduct->product->uuid,
                storageLocationUuid: $location->uuid,
                consumedQuantity: $quantity,
                unit: $data->unit->baseUnit(),
                locationQuantity: $stock->quantity,
                totalQuantity: StockQuantity::databaseValue(
                    $this->inventory->totalStockQuantity($householdProduct),
                ),
            );
        });
    }
}
