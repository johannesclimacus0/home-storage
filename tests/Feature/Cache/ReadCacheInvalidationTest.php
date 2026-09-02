<?php

namespace Tests\Feature\Cache;

use App\Actions\Inventory\AddStockAction;
use App\DTO\Inventory\AddStockData;
use App\Enums\MeasurementUnit;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

final class ReadCacheInvalidationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_creating_product_invalidates_cached_catalog(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Test milk']);

        $this->actingAs($user)
            ->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1, 'data');

        Product::factory()->create(['name' => 'Test bread']);

        $this->actingAs($user)
            ->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('data.0.name', 'Test bread');
    }

    public function test_creating_recipe_invalidates_cached_recipe_list(): void
    {
        $user = User::factory()->create();
        Recipe::factory()->create(['title' => 'Test soup']);

        $this->actingAs($user)
            ->getJson('/api/recipes')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        Recipe::factory()->create(['title' => 'Test bread']);

        $this->actingAs($user)
            ->getJson('/api/recipes')
            ->assertOk()
            ->assertJsonPath('meta.total', 2)
            ->assertJsonPath('data.0.title', 'Test bread');
    }

    public function test_stock_change_invalidates_cached_recipe_availability(): void
    {
        $household = Household::factory()->create();
        $member = User::factory()->create();
        HouseholdMembership::factory()->for($household)->for($member, 'user')->create();
        $product = Product::factory()->mass()->create(['name' => 'Test flour']);
        $householdProduct = HouseholdProduct::factory()->for($household)->for($product)->create();
        $location = StorageLocation::factory()->for($household)->create();
        $recipe = Recipe::factory()->create(['title' => 'Test bread']);
        RecipeIngredient::factory()->for($recipe)->for($product)->create([
            'quantity' => '1000.000',
        ]);
        $url = "/api/households/{$household->uuid}/recipes/{$recipe->uuid}/availability";

        $this->actingAs($member)
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.can_make', false);

        $this->app->make(AddStockAction::class)->handle(new AddStockData(
            householdUuid: $household->uuid,
            actorUserId: $member->getKey(),
            productUuid: $householdProduct->product->uuid,
            storageLocationUuid: $location->uuid,
            quantity: '1',
            unit: MeasurementUnit::Kilogram
        ));

        $this->actingAs($member)
            ->getJson($url)
            ->assertOk()
            ->assertJsonPath('data.can_make', true)
            ->assertJsonPath('data.ingredients.0.available_quantity', '1000.000');
    }
}
