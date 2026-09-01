<?php

namespace Tests\Feature\Recipes;

use App\Enums\HouseholdRole;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdProduct;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\RecipeIngredient;
use App\Models\ShoppingListItem;
use App\Models\Stock;
use App\Models\StorageLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class HouseholdRecipeAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_filter_recipes_by_household_availability(): void
    {
        [$household, $member] = $this->householdWithMember();
        $flour = Product::factory()->mass()->create(['name' => 'Test flour']);
        $spice = Product::factory()->mass()->create(['name' => 'Test spice']);
        $availableRecipe = Recipe::factory()->create(['title' => 'Available recipe']);
        RecipeIngredient::factory()->for($availableRecipe)->for($flour)->create([
            'quantity' => '500.000',
        ]);
        RecipeIngredient::factory()->optional()->for($availableRecipe)->for($spice)->create([
            'quantity' => '50.000',
            'position' => 2,
        ]);
        $missingRecipe = Recipe::factory()->create(['title' => 'Missing recipe']);
        RecipeIngredient::factory()->for($missingRecipe)->for($flour)->create([
            'quantity' => '700.000',
        ]);
        $this->addStock($household, $flour, '500.000');
        $baseUrl = "/api/households/{$household->uuid}/recipes";

        $this->actingAs($member)
            ->getJson("{$baseUrl}?availability=available")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $availableRecipe->uuid)
            ->assertJsonPath('data.0.availability.can_make', true)
            ->assertJsonPath('data.0.availability.missing_required_count', 0);

        $this->actingAs($member)
            ->getJson("{$baseUrl}?availability=missing")
            ->assertOk()
            ->assertJsonPath('meta.total', 1)
            ->assertJsonPath('data.0.uuid', $missingRecipe->uuid)
            ->assertJsonPath('data.0.availability.can_make', false)
            ->assertJsonPath('data.0.availability.missing_required_count', 1);
    }

    public function test_availability_sums_all_locations_and_describes_shortage(): void
    {
        [$household, $member] = $this->householdWithMember();
        $product = Product::factory()->volume()->create(['name' => 'Test milk']);
        $recipe = Recipe::factory()->create();
        RecipeIngredient::factory()->for($recipe)->for($product)->create([
            'quantity' => '1000.000',
        ]);
        $householdProduct = HouseholdProduct::factory()->for($household)->for($product)->create();
        Stock::factory()->for($householdProduct)->create([
            'storage_location_id' => StorageLocation::factory()->for($household),
            'quantity' => '300.000',
        ]);
        Stock::factory()->for($householdProduct)->create([
            'storage_location_id' => StorageLocation::factory()->for($household),
            'quantity' => '200.000',
        ]);

        $this->actingAs($member)
            ->getJson("/api/households/{$household->uuid}/recipes/{$recipe->uuid}/availability")
            ->assertOk()
            ->assertJsonPath('data.can_make', false)
            ->assertJsonPath('data.missing_required_count', 1)
            ->assertJsonPath('data.ingredients.0.product.uuid', $product->uuid)
            ->assertJsonPath('data.ingredients.0.required_quantity', '1000.000')
            ->assertJsonPath('data.ingredients.0.available_quantity', '500.000')
            ->assertJsonPath('data.ingredients.0.missing_quantity', '500.000')
            ->assertJsonPath('data.ingredients.0.sufficient', false);
    }

    public function test_member_can_add_only_missing_required_ingredients_to_shopping_list(): void
    {
        [$household, $member] = $this->householdWithMember();
        $flour = Product::factory()->mass()->create(['name' => 'Test flour']);
        $milk = Product::factory()->volume()->create(['name' => 'Test milk']);
        $spice = Product::factory()->mass()->create(['name' => 'Test spice']);
        $recipe = Recipe::factory()->create();
        RecipeIngredient::factory()->for($recipe)->for($flour)->create(['quantity' => '500.000']);
        RecipeIngredient::factory()->for($recipe)->for($milk)->create([
            'quantity' => '100.000',
            'position' => 2,
        ]);
        RecipeIngredient::factory()->optional()->for($recipe)->for($spice)->create([
            'quantity' => '20.000',
            'position' => 3,
        ]);
        $this->addStock($household, $flour, '200.000');
        ShoppingListItem::factory()->for($household)->for($flour)->for($member, 'addedBy')->create([
            'quantity' => '400.000',
            'completed_at' => null,
        ]);
        $url = "/api/households/{$household->uuid}/recipes/{$recipe->uuid}/shopping-list-items";

        $this->actingAs($member)
            ->postJson($url)
            ->assertOk()
            ->assertJsonCount(2, 'data');

        $this->assertDatabaseCount('shopping_list_items', 2);
        $this->assertDatabaseHas('shopping_list_items', [
            'household_id' => $household->getKey(),
            'product_id' => $flour->getKey(),
            'quantity' => '400.000',
            'completed_at' => null,
        ]);
        $this->assertDatabaseHas('shopping_list_items', [
            'household_id' => $household->getKey(),
            'product_id' => $milk->getKey(),
            'quantity' => '100.000',
            'completed_at' => null,
        ]);
        $this->assertDatabaseMissing('shopping_list_items', [
            'household_id' => $household->getKey(),
            'product_id' => $spice->getKey(),
        ]);

        $this->actingAs($member)->postJson($url)->assertOk();
        $this->assertDatabaseCount('shopping_list_items', 2);
    }

    public function test_outsider_cannot_access_household_recipe_availability(): void
    {
        $household = Household::factory()->create();
        $recipe = Recipe::factory()->create();
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/households/{$household->uuid}/recipes")
            ->assertForbidden();
        $this->actingAs($outsider)
            ->getJson("/api/households/{$household->uuid}/recipes/{$recipe->uuid}/availability")
            ->assertForbidden();
        $this->actingAs($outsider)
            ->postJson("/api/households/{$household->uuid}/recipes/{$recipe->uuid}/shopping-list-items")
            ->assertForbidden();
    }

    private function householdWithMember(): array
    {
        $household = Household::factory()->create();
        $member = User::factory()->create();
        HouseholdMembership::factory()->for($household)->for($member, 'user')->create([
            'role' => HouseholdRole::Member,
        ]);

        return [$household, $member];
    }

    private function addStock(Household $household, Product $product, string $quantity): void
    {
        $householdProduct = HouseholdProduct::factory()->for($household)->for($product)->create();
        Stock::factory()->for($householdProduct)->create([
            'storage_location_id' => StorageLocation::factory()->for($household),
            'quantity' => $quantity,
        ]);
    }
}
