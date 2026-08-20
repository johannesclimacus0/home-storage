<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\DTO\Inventory\AddProductToHouseholdData;
use App\DTO\Inventory\AddProductToHouseholdResult;
use App\Exceptions\Inventory\HouseholdProductConflict;
use App\Services\Inventory\LowStockTracker;
use App\Support\Inventory\LowStockThreshold;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class AddProductToHouseholdAction
{
    public function __construct(
        private InventoryRepository $inventory,
        private HouseholdRepository $households,
        private LowStockTracker $lowStockTracker,
    ) {}

    public function handle(AddProductToHouseholdData $data): AddProductToHouseholdResult
    {
        return DB::transaction(function () use ($data): AddProductToHouseholdResult {
            $household = $this->households->findByUuidForUpdate(
                $data->householdUuid,
            );

            $this->households->findMembershipForUpdate(
                $household,
                $data->actorUserId,
            );

            $product = $this->inventory->findProductByUuid($data->productUuid);
            $threshold = LowStockThreshold::normalize(
                $data->lowStockThreshold,
                $product->measurement_type,
            );

            if ($this->inventory->householdProductExists($household, $product)) {
                throw new HouseholdProductConflict('Household product already exists.');
            }

            $householdProduct = $this->inventory->createHouseholdProduct(
                household: $household,
                product: $product,
                lowStockThreshold: $threshold,
            );

            $this->lowStockTracker->refresh($householdProduct, CarbonImmutable::now());

            return new AddProductToHouseholdResult(
                householdUuid: $household->uuid,
                productUuid: $product->uuid,
                productName: $product->name,
                measurementType: $product->measurement_type,
                lowStockThreshold: $householdProduct->low_stock_threshold,
            );
        });
    }
}
