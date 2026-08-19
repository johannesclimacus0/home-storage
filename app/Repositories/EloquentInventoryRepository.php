<?php

namespace App\Repositories;

use App\Contracts\Inventory\InventoryRepository;
use App\Models\Household;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StorageLocation;
use Illuminate\Database\Eloquent\Collection;

final class EloquentInventoryRepository implements InventoryRepository
{
    public function storageLocationExists(Household $household, string $name): bool
    {
        return StorageLocation::query()
            ->where('name', $name)
            ->where('household_id', $household->getKey())
            ->exists();
    }

    public function createStorageLocation(Household $household, string $name): StorageLocation
    {
        return $household->storageLocations()->create([
            'name' => $name,
        ]);
    }

    public function findProductByUuid(string $uuid): Product
    {
        return Product::query()
            ->where('uuid', $uuid)
            ->firstOrFail();
    }

    public function householdProductExists(Household $household, Product $product): bool
    {
        return HouseholdProduct::query()
            ->where('product_id', $product->getKey())
            ->where('household_id', $household->getKey())
            ->exists();
    }

    public function createHouseholdProduct(
        Household $household,
        Product $product,
        string $lowStockThreshold,
    ): HouseholdProduct {
        return $household->householdProducts()->create([
            'product_id' => $product->getKey(),
            'low_stock_threshold' => $lowStockThreshold,
        ]);
    }

    /** @return Collection<int, HouseholdProduct> */
    public function findHouseholdProducts(Household $household): Collection
    {
        return HouseholdProduct::query()
            ->where('household_id', $household->getKey())
            ->with('product')
            ->withSum('stocks', 'quantity')
            ->latest()
            ->get();
    }

    public function findHouseholdProduct(
        Household $household,
        string $productUuid,
    ): HouseholdProduct {
        return HouseholdProduct::query()
            ->where('household_id', $household->getKey())
            ->whereHas('product', fn ($query) => $query->where('uuid', $productUuid))
            ->with('product')
            ->withSum('stocks', 'quantity')
            ->firstOrFail();
    }

    public function findHouseholdProductForUpdate(
        Household $household,
        string $productUuid,
    ): HouseholdProduct {
        return HouseholdProduct::query()
            ->where('household_id', $household->getKey())
            ->whereHas('product', fn ($query) => $query->where('uuid', $productUuid))
            ->with('product')
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function updateLowStockThreshold(
        HouseholdProduct $householdProduct,
        string $threshold,
    ): void {
        $householdProduct->updateOrFail([
            'low_stock_threshold' => $threshold,
        ]);
    }

    public function deleteHouseholdProduct(HouseholdProduct $householdProduct): void
    {
        $householdProduct->deleteOrFail();
    }

    public function findStorageLocationForUpdate(
        Household $household,
        string $locationUuid,
    ): StorageLocation {
        return StorageLocation::query()
            ->where('household_id', $household->getKey())
            ->where('uuid', $locationUuid)
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function findStockForUpdate(
        HouseholdProduct $householdProduct,
        StorageLocation $storageLocation,
    ): ?Stock {
        return Stock::query()
            ->where('household_product_id', $householdProduct->getKey())
            ->where('storage_location_id', $storageLocation->getKey())
            ->lockForUpdate()
            ->first();
    }

    public function createStock(
        HouseholdProduct $householdProduct,
        StorageLocation $storageLocation,
        string $quantity,
    ): Stock {
        return Stock::query()->create([
            'household_product_id' => $householdProduct->getKey(),
            'storage_location_id' => $storageLocation->getKey(),
            'quantity' => $quantity,
        ]);
    }

    public function incrementStock(Stock $stock, string $quantity): void
    {
        Stock::query()
            ->whereKey($stock->getKey())
            ->increment('quantity', $quantity);

        $stock->refresh();
    }

    public function totalStockQuantity(HouseholdProduct $householdProduct): string
    {
        return (string) Stock::query()
            ->where('household_product_id', $householdProduct->getKey())
            ->sum('quantity');
    }

    public function findStockForUpdateOrFail(HouseholdProduct $householdProduct, StorageLocation $storageLocation): Stock
    {
        return Stock::query()
            ->where('household_product_id', $householdProduct->getKey())
            ->where('storage_location_id', $storageLocation->getKey())
            ->lockForUpdate()
            ->firstOrFail();
    }

    public function decrementStock(Stock $stock, string $quantity): void
    {
        Stock::query()
            ->whereKey($stock->getKey())
            ->decrement('quantity', $quantity);

        $stock->refresh();
    }
}
