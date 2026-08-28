<?php

namespace App\Actions\Shopping;

use App\Actions\Inventory\AddStockAction;
use App\Contracts\Households\HouseholdRepository;
use App\Contracts\Shopping\ShoppingListRepository;
use App\DTO\Inventory\AddStockData;
use App\DTO\Shopping\PurchaseShoppingListItemData;
use App\Enums\MeasurementType;
use App\Enums\MeasurementUnit;
use App\Exceptions\Shopping\ShoppingListItemAlreadyCompleted;
use App\Models\ShoppingListItem;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

final readonly class PurchaseShoppingListItemAction
{
    public function __construct(
        private HouseholdRepository $households,
        private ShoppingListRepository $shoppingList,
        private AddStockAction $addStock
    ) {}

    public function handle(PurchaseShoppingListItemData $data): ShoppingListItem
    {
        return DB::transaction(function () use ($data): ShoppingListItem {
            $household = $this->households->findByUuidForUpdate($data->householdUuid);
            $this->households->findMembershipForUpdate($household, $data->actorUserId);
            $item = $this->shoppingList->findForHouseholdForUpdate($household, $data->itemUuid);

            if ($item->completed_at !== null) {
                throw new ShoppingListItemAlreadyCompleted(__('messages.shopping.already_completed'));
            }

            $unit = match ($item->product->measurement_type) {
                MeasurementType::Mass => MeasurementUnit::Gram,
                MeasurementType::Volume => MeasurementUnit::Milliliter,
                MeasurementType::Count => MeasurementUnit::Piece,
            };

            $this->addStock->handle(new AddStockData(
                householdUuid: $household->uuid,
                actorUserId: $data->actorUserId,
                productUuid: $item->product->uuid,
                storageLocationUuid: $data->storageLocationUuid,
                quantity: $item->quantity,
                unit: $unit
            ));

            $this->shoppingList->markCompleted($item, CarbonImmutable::now());

            return $item->refresh()->load(['product', 'addedBy']);
        });
    }
}
