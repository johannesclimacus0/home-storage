<?php

namespace App\Contracts\Inventory;

use App\Models\Household;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StorageLocation;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Collection;

interface InventoryRepository
{
    public function storageLocationExists(
        Household $household,
        string $name,
        ?StorageLocation $ignore = null,
    ): bool;

    public function createStorageLocation(Household $household, string $name): StorageLocation;

    /** @return Collection<int, StorageLocation> */
    public function findStorageLocations(Household $household): Collection;

    public function findStorageLocation(
        Household $household,
        string $locationUuid
    ): StorageLocation;

    public function updateStorageLocation(StorageLocation $storageLocation, string $name): void;

    public function storageLocationHasStock(StorageLocation $storageLocation): bool;

    public function deleteStorageLocation(StorageLocation $storageLocation): void;

    public function findProductByUuid(string $uuid): Product;

    public function householdProductExists(Household $household, Product $product): bool;

    public function createHouseholdProduct(
        Household $household,
        Product $product,
        string $lowStockThreshold,
    ): HouseholdProduct;

    /** @return Collection<int, HouseholdProduct> */
    public function findHouseholdProducts(Household $household): Collection;

    public function findHouseholdProduct(
        Household $household,
        string $productUuid,
    ): HouseholdProduct;

    public function findHouseholdProductForUpdate(
        Household $household,
        string $productUuid,
    ): HouseholdProduct;

    public function updateLowStockThreshold(
        HouseholdProduct $householdProduct,
        string $threshold,
    ): void;

    public function deleteHouseholdProduct(HouseholdProduct $householdProduct): void;

    public function findStorageLocationForUpdate(
        Household $household,
        string $locationUuid,
    ): StorageLocation;

    public function findStockForUpdate(
        HouseholdProduct $householdProduct,
        StorageLocation $storageLocation,
    ): ?Stock;

    public function createStock(
        HouseholdProduct $householdProduct,
        StorageLocation $storageLocation,
        string $quantity
    ): Stock;

    public function incrementStock(Stock $stock, string $quantity): void;

    public function totalStockQuantity(HouseholdProduct $householdProduct): string;

    public function findStockForUpdateOrFail(
        HouseholdProduct $householdProduct,
        StorageLocation $storageLocation
    ): Stock;

    public function decrementStock(Stock $stock, string $quantity): void;

    public function updateLowStockSince(
        HouseholdProduct $householdProduct,
        ?CarbonImmutable $lowStockSince
    ): void;

    /**
     * @return Collection<int, HouseholdProduct>
     */
    public function findLowStockProducts(Household $household): Collection;

    public function findHouseholdProductWithRecipients(int $householdProductId): HouseholdProduct;
}
