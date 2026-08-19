<?php

namespace Tests\Unit\Policies;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\User;
use App\Policies\HouseholdPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdPolicyTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_owner_can_manage_members_and_transfer_ownership(): void
    {
        [$household, $owner, $member] = $this->householdWithRoles();
        $policy = new HouseholdPolicy;

        $this->assertTrue($policy->addMember($owner, $household));
        $this->assertTrue($policy->transferOwnership($owner, $household));
        $this->assertFalse($policy->addMember($member, $household));
        $this->assertFalse($policy->transferOwnership($member, $household));
        $this->assertTrue($policy->update($owner, $household));
        $this->assertTrue($policy->delete($owner, $household));
        $this->assertTrue($policy->removeMember($owner, $household));
        $this->assertFalse($policy->update($member, $household));
        $this->assertFalse($policy->delete($member, $household));
        $this->assertFalse($policy->removeMember($member, $household));
    }

    public function test_every_member_can_manage_inventory_but_outsider_cannot(): void
    {
        [$household, $owner, $member] = $this->householdWithRoles();
        $outsider = User::factory()->create();
        $policy = new HouseholdPolicy;

        $this->assertTrue($policy->manageInventory($owner, $household));
        $this->assertTrue($policy->manageInventory($member, $household));
        $this->assertFalse($policy->manageInventory($outsider, $household));
        $this->assertTrue($policy->view($owner, $household));
        $this->assertTrue($policy->view($member, $household));
        $this->assertTrue($policy->leave($member, $household));
        $this->assertFalse($policy->view($outsider, $household));
        $this->assertFalse($policy->leave($outsider, $household));
    }

    private function householdWithRoles(): array
    {
        $household = Household::factory()->create();
        $owner = User::factory()->create();
        $member = User::factory()->create();

        HouseholdMembership::factory()->create([
            'household_id' => $household->id,
            'user_id' => $owner->id,
            'role' => HouseholdRole::Owner,
        ]);
        HouseholdMembership::factory()->create([
            'household_id' => $household->id,
            'user_id' => $member->id,
            'role' => HouseholdRole::Member,
        ]);

        return [$household, $owner, $member];
    }
}
