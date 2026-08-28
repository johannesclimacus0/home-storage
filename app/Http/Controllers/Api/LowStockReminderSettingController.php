<?php

namespace App\Http\Controllers\Api;

use App\Actions\Inventory\UpdateLowStockReminderSettingsAction;
use App\DTO\Inventory\UpdateLowStockReminderSettingsData;
use App\Http\Controllers\Controller;
use App\Http\Requests\Inventory\UpdateLowStockReminderSettingsRequest;
use App\Http\Resources\LowStockReminderSettingsResource;
use App\Models\Household;

final class LowStockReminderSettingController extends Controller
{
    public function update(
        UpdateLowStockReminderSettingsRequest $request,
        Household $household,
        UpdateLowStockReminderSettingsAction $action
    ): LowStockReminderSettingsResource {
        $membership = $action->handle(new UpdateLowStockReminderSettingsData(
            household: $household,
            user: $request->user(),
            enabled: $request->boolean('enabled'),
            intervalHours: $request->integer('interval_hours')
        ));

        return new LowStockReminderSettingsResource($membership->load('household'));
    }
}
