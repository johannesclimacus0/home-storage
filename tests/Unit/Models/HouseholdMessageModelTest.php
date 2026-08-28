<?php

namespace Tests\Unit\Models;

use App\Models\Household;
use App\Models\HouseholdMessage;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HouseholdMessageModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_household_message_has_expected_relationships_and_casts(): void
    {
        $message = HouseholdMessage::factory()->create();

        $this->assertInstanceOf(Household::class, $message->household);
        $this->assertInstanceOf(User::class, $message->sender);
        $this->assertIsString($message->content);
        $this->assertNull($message->edited_at);
        $this->assertNull($message->deleted_at);

        $editedMessage = HouseholdMessage::factory()
            ->edited()
            ->create();

        $deletedMessage = HouseholdMessage::factory()
            ->deleted()
            ->create();

        $this->assertInstanceOf(
            CarbonImmutable::class,
            $editedMessage->edited_at
        );

        $this->assertInstanceOf(
            CarbonImmutable::class,
            $deletedMessage->deleted_at
        );
    }
}
