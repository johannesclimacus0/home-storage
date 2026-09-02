<?php

namespace App\Actions\Inventory;

use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Inventory\InventoryRepository;
use App\Models\HouseholdProduct;
use App\Support\Cache\HouseholdCache;
use Illuminate\Support\Collection;

final readonly class ListHouseholdProductsAction
{
    public function __construct(
        private HouseholdRepository $households,
        private InventoryRepository $inventory,
        private HouseholdCache $cache
    ) {}

    /** @return Collection<int, array<string, mixed>> */
    public function handle(string $householdUuid, int $actorUserId): Collection
    {
        $household = $this->households->findByUuid($householdUuid);
        $this->households->findMembership($household, $actorUserId);

        return $this->cache->rememberInventory(
            $household->uuid,
            fn (): Collection => $this->inventory
                ->findHouseholdProducts($household)
                ->map(
                    fn (HouseholdProduct $householdProduct): array => $this->toCacheArray($householdProduct)
                )
        );
    }

    private function toCacheArray(HouseholdProduct $householdProduct): array
    {
        return [
            'uuid' => $householdProduct->product->uuid,
            'name' => $householdProduct->product->name,
            'measurement_type' => $householdProduct->product->measurement_type->value,
            'low_stock_threshold' => $householdProduct->low_stock_threshold,
            'total_quantity' => (string) ($householdProduct->stocks_sum_quantity ?? '0.000'),
            'is_low_stock' => $householdProduct->low_stock_since !== null,
            'low_stock_since' => $householdProduct->low_stock_since?->toISOString(),
        ];
    }
}
