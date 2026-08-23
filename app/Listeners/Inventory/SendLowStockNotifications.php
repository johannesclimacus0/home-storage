<?php

namespace App\Listeners\Inventory;

use App\Contracts\Inventory\InventoryRepository;
use App\Events\Inventory\ProductBecameLowStock;
use App\Models\User;
use App\Notifications\Inventory\LowStockNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

final readonly class SendLowStockNotifications
{
    public function __construct(private InventoryRepository $inventory) {}

    public function handle(ProductBecameLowStock $event): void
    {
        $householdProduct = $this->inventory->findHouseholdProductWithRecipients(
            $event->householdProductId,
        );

        /**
         * @var Collection<int, User>
         */
        $users = $householdProduct
            ->household
            ->householdMemberships
            ->pluck('user');

        $notification = new LowStockNotification(
            householdUuid: $householdProduct->household->uuid,
            householdName: $householdProduct->household->name,
            productUuid: $householdProduct->product->uuid,
            productName: $householdProduct->product->name,
            measurementType: $householdProduct->product->measurement_type->value,
            totalQuantity: $event->totalQuantity,
            lowStockThreshold: $householdProduct->low_stock_threshold,
            occurredAt: $event->occurredAt,
        );

        Notification::send($users, $notification);
    }
}
