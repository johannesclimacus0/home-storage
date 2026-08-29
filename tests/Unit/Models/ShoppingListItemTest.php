<?php

namespace Tests\Unit\Models;

use App\Models\Household;
use App\Models\Product;
use App\Models\ShoppingListItem;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShoppingListItemTest extends TestCase
{
    use RefreshDatabase;

    public function test_shopping_list_item_has_expected_relationships_and_casts(): void
    {
        $shoppingListItem = ShoppingListItem::factory()->create(['quantity' => 2]);

        $this->assertInstanceOf(Household::class, $shoppingListItem->household);
        $this->assertInstanceOf(Product::class, $shoppingListItem->product);
        $this->assertInstanceOf(User::class, $shoppingListItem->addedBy);
        $this->assertSame('2.000', $shoppingListItem->quantity);
        $this->assertNull($shoppingListItem->completed_at);
    }

    public function test_completed_factory_state_sets_completion_date(): void
    {
        $shoppingListItem = ShoppingListItem::factory()
            ->completed()
            ->create();

        $this->assertNotNull($shoppingListItem->completed_at);
        $this->assertInstanceOf(CarbonImmutable::class, $shoppingListItem->completed_at);
    }
}
