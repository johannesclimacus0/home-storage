<?php

namespace App\Actions\Inventory;

use App\Contracts\Inventory\LowStockReminderRepository;
use App\Notifications\Inventory\ProductLowStockNotification;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Notification;

final readonly class SendDueLowStockRemindersAction
{
    public function __construct(
        private LowStockReminderRepository $reminders
    ) {}

    public function handle(CarbonImmutable $now): int
    {
        $candidates = $this->reminders->dueAt($now);
        $sentCount = 0;

        foreach ($candidates as $candidate) {
            $membership = $candidate->membership;
            $householdProduct = $candidate->householdProduct;

            Notification::send(
                $membership->user,
                new ProductLowStockNotification(
                    householdUuid: $householdProduct->household->uuid,
                    householdName: $householdProduct->household->name,
                    productUuid: $householdProduct->product->uuid,
                    productName: $householdProduct->product->name,
                    measurementType: $householdProduct->product->measurement_type,
                    totalQuantity: $candidate->totalQuantity,
                    threshold: $householdProduct->low_stock_threshold,
                    becameLowAt: $householdProduct->low_stock_since
                )
            );

            $this->reminders->markDispatched(
                membership: $membership,
                householdProduct: $householdProduct,
                at: $now
            );

            $sentCount++;
        }

        return $sentCount;
    }
}
