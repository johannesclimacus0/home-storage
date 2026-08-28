<?php

namespace App\Listeners;

use App\Contracts\Inventory\LowStockReminderRepository;
use App\Events\Inventory\ProductBecameLowStock;
use App\Models\HouseholdProduct;
use App\Notifications\Inventory\ProductLowStockNotification;
use Illuminate\Support\Facades\Notification;

final class SendProductLowStockNotification
{
    public function __construct(
        private LowStockReminderRepository $reminders
    ) {}

    public function handle(ProductBecameLowStock $event): void
    {
        $householdProduct = HouseholdProduct::query()
            ->with('product')
            ->with('household.householdMemberships.user')
            ->whereKey($event->householdProductId)
            ->sole();

        $users = $householdProduct->household
            ->householdMemberships
            ->pluck('user');

        $memberships = $householdProduct
            ->household
            ->householdMemberships;

        Notification::send(
            $users,
            new ProductLowStockNotification(
                householdUuid: $householdProduct->household->uuid,
                householdName: $householdProduct->household->name,
                productUuid: $householdProduct->product->uuid,
                productName: $householdProduct->product->name,
                measurementType: $householdProduct->product->measurement_type,
                totalQuantity: $event->totalQuantity,
                threshold: $householdProduct->low_stock_threshold,
                becameLowAt: $event->occurredAt,
            ),
        );

        foreach ($memberships as $membership) {
            $this->reminders->markDispatched(
                membership: $membership,
                householdProduct: $householdProduct,
                at: $event->occurredAt
            );
        }
    }
}
