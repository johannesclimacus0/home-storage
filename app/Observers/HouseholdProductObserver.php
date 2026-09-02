<?php

namespace App\Observers;

use App\Models\HouseholdProduct;
use App\Support\Cache\HouseholdCache;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class HouseholdProductObserver implements ShouldHandleEventsAfterCommit
{
    public function __construct(private HouseholdCache $cache) {}

    /**
     * Handle the HouseholdProduct "created" event.
     */
    public function created(HouseholdProduct $householdProduct): void
    {
        $this->forgetInventory($householdProduct);
    }

    /**
     * Handle the HouseholdProduct "updated" event.
     */
    public function updated(HouseholdProduct $householdProduct): void
    {
        $this->forgetInventory($householdProduct);
    }

    /**
     * Handle the HouseholdProduct "deleted" event.
     */
    public function deleted(HouseholdProduct $householdProduct): void
    {
        $this->forgetInventory($householdProduct);
    }

    private function forgetInventory(HouseholdProduct $householdProduct): void
    {
        $this->cache->forgetInventory($householdProduct->household->uuid);
    }
}
