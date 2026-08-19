<?php

namespace App\Contracts\Inventory;

use App\Models\Household;
use App\Models\StorageLocation;

interface InventoryRepository
{
    public function storageLocationExists(Household $household, string $name): bool;

    public function createStorageLocation(Household $household, string $name): StorageLocation;
}
