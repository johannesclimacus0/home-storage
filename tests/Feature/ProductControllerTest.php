<?php

namespace Tests\Feature;

use App\Enums\MeasurementType;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

final class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_list_products(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->getJson('/api/products');

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'uuid',
                    'name',
                    'measurement_type',
                ],
            ],
        ]);
    }

    public function test_products_are_ordered_by_name(): void
    {
        $user = User::factory()->create();

        Product::factory()->create(['name' => 'Sugar']);
        Product::factory()->create(['name' => 'Milk']);
        Product::factory()->create(['name' => 'Eggs']);

        $response = $this->actingAs($user)
            ->getJson('/api/products');

        $response->assertOk();

        $this->assertSame(
            ['Eggs', 'Milk', 'Sugar'],
            $response->json('data.*.name'),
        );
    }

    public function test_verified_user_can_view_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/products/{$product->uuid}");

        $response->assertOk();
        $response->assertJsonPath('data.uuid', $product->uuid);
        $response->assertJsonPath('data.name', $product->name);
        $response->assertJsonPath(
            'data.measurement_type',
            $product->measurement_type->value,
        );
    }

    public function test_unknown_product_returns_not_found(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->getJson('/api/products/' . Str::uuid())
            ->assertNotFound();
    }

    public function test_guest_cannot_list_products(): void
    {
        $this->getJson('/api/products')
            ->assertUnauthorized();
    }

    public function test_guest_cannot_view_product(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->uuid}")
            ->assertUnauthorized();
    }

    public function test_unverified_user_cannot_access_product_catalog(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->getJson('/api/products')
            ->assertForbidden();
    }

    public function test_verified_user_can_create_product(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/products', [
            'name' => 'Giant Kitten',
            'measurement_type' => MeasurementType::Mass->value,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Giant Kitten')
            ->assertJsonPath('data.measurement_type', MeasurementType::Mass->value)
            ->assertJsonStructure([
                'data' => ['uuid', 'name', 'measurement_type'],
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'Giant Kitten',
            'measurement_type' => MeasurementType::Mass->value,
        ]);
    }

    public function test_product_name_is_normalized_when_created(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/products', [
            'name' => '  Giant   Kitten  ',
            'measurement_type' => MeasurementType::Mass->value,
        ])->assertCreated()
            ->assertJsonPath('data.name', 'Giant Kitten');

        $this->assertDatabaseHas('products', ['name' => 'Giant Kitten']);
    }

    public function test_product_fields_are_validated(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson('/api/products', [
            'name' => ' ',
            'measurement_type' => 'distance',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['name', 'measurement_type']);

        $this->assertDatabaseCount('products', 0);
    }

    public function test_product_name_is_unique_ignoring_case_and_extra_spaces(): void
    {
        $user = User::factory()->create();
        Product::factory()->create(['name' => 'Giant Kitten']);

        $this->actingAs($user)->postJson('/api/products', [
            'name' => '  giant   Kitten ',
            'measurement_type' => MeasurementType::Volume->value,
        ])->assertConflict()
            ->assertJsonPath('message', 'Продукт с таким названием уже существует.');

        $this->assertDatabaseCount('products', 1);
    }

    public function test_guest_and_unverified_user_cannot_create_product(): void
    {
        $payload = [
            'name' => 'Milk',
            'measurement_type' => MeasurementType::Volume->value,
        ];

        $this->postJson('/api/products', $payload)
            ->assertUnauthorized();

        $this->actingAs(User::factory()->unverified()->create())
            ->postJson('/api/products', $payload)
            ->assertForbidden();

        $this->assertDatabaseCount('products', 0);
    }
}
