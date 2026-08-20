<?php

namespace Tests\Unit\Models;

use App\Enums\MeasurementUnit;
use App\Enums\StockMovementType;
use App\Models\StockMovement;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class StockMovementTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_creates_casted_movement_with_relationships(): void
    {
        $movement = StockMovement::factory()->create();

        $this->assertNotNull($movement->uuid);
        $this->assertSame(StockMovementType::Purchase, $movement->type);
        $this->assertSame(MeasurementUnit::Gram, $movement->input_unit);
        $this->assertSame('10.000', $movement->quantity_delta);
        $this->assertTrue($movement->household()->exists());
        $this->assertTrue($movement->householdProduct()->exists());
        $this->assertTrue($movement->product()->exists());
        $this->assertTrue($movement->storageLocation()->exists());
        $this->assertTrue($movement->actor()->exists());
    }
}
