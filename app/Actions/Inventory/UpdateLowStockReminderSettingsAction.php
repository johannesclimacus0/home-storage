<?php

namespace App\Actions\Inventory;

use App\Contracts\Inventory\LowStockReminderRepository;
use App\DTO\Inventory\UpdateLowStockReminderSettingsData;
use App\Models\HouseholdMembership;

final readonly class UpdateLowStockReminderSettingsAction
{
    public function __construct(
        private LowStockReminderRepository $repository
    ) {}

    public function handle(UpdateLowStockReminderSettingsData $data): HouseholdMembership
    {
        $membership = $this->repository->findMembership($data->household, $data->user);

        return $this->repository->updateSettings(
            $membership,
            $data->enabled,
            $data->intervalHours
        );
    }
}
