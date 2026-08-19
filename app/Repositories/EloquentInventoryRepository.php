<?php

namespace App\Repositories;

use App\Contracts\Inventory\InventoryRepository;
use App\Models\Household;
use App\Models\StorageLocation;

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
}
