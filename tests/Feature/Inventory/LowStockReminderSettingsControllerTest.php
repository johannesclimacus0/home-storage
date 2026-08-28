<?php

namespace Tests\Feature\Inventory;

use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowStockReminderSettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_update_own_household_reminder_settings(): void
    {
        $user = User::factory()->create();
        $household = Household::factory()->create();
        $membership = HouseholdMembership::factory()->for($household)->for($user)->create();

        $response = $this->actingAs($user)->patchJson(
            "/api/households/{$household->uuid}/low-stock-reminder-settings",
            [
                'enabled' => true,
                'interval_hours' => 168,
            ]
        );

        $response->assertOk();
        $response->assertJsonPath('data.household_uuid', $household->uuid);
        $response->assertJsonPath('data.enabled', true);
        $response->assertJsonPath('data.interval_hours', 168);

        $membership->refresh();
        $this->assertTrue($membership->low_stock_reminders_enabled);
        $this->assertSame(168, $membership->low_stock_reminder_interval_hours);
    }

    public function test_outsider_cannot_update_household_reminder_settings(): void
    {
        $user = User::factory()->create();
        $household = Household::factory()->create();

        $this->actingAs($user)
            ->patchJson(
                "/api/households/{$household->uuid}/low-stock-reminder-settings",
                ['enabled' => true, 'interval_hours' => 24]
            )
            ->assertForbidden();
    }

    public function test_reminder_settings_validate_interval(): void
    {
        $user = User::factory()->create();
        $household = Household::factory()->create();
        HouseholdMembership::factory()->for($household)->for($user)->create();

        $this->actingAs($user)
            ->patchJson(
                "/api/households/{$household->uuid}/low-stock-reminder-settings",
                ['enabled' => true, 'interval_hours' => 0]
            )
            ->assertJsonValidationErrors('interval_hours');
    }
}
