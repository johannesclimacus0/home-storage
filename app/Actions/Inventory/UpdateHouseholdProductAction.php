<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\DTO\Inventory\UpdateHouseholdProductData;
use App\Models\HouseholdProduct;
use App\Services\Inventory\LowStockTracker;
use App\Support\Inventory\LowStockThreshold;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class UpdateHouseholdProductAction
{
    public function __construct(
        private HouseholdRepository $households,
        private InventoryRepository $inventory,
        private LowStockTracker $lowStockTracker,
    ) {}

    public function handle(UpdateHouseholdProductData $data): HouseholdProduct
    {
        return DB::transaction(function () use ($data): HouseholdProduct {
            $household = $this->households->findByUuidForUpdate($data->householdUuid);
            $this->households->findMembershipForUpdate($household, $data->actorUserId);
            $householdProduct = $this->inventory->findHouseholdProductForUpdate(
                $household,
                $data->productUuid,
            );
            $threshold = LowStockThreshold::normalize(
                $data->lowStockThreshold,
                $householdProduct->product->measurement_type,
            );

            $this->inventory->updateLowStockThreshold($householdProduct, $threshold);
            $this->lowStockTracker->refresh($householdProduct, CarbonImmutable::now());

            return $householdProduct->refresh()->load('product')->loadSum('stocks', 'quantity');
        });
    }
}
