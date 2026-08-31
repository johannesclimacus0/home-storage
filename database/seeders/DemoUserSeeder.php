<?php

namespace Database\Seeders;

use App\Enums\HouseholdRole;
use App\Enums\MeasurementType;
use App\Enums\MeasurementUnit;
use App\Enums\StockMovementType;
use App\Models\Household;
use App\Models\HouseholdMembership;
use App\Models\HouseholdMessage;
use App\Models\HouseholdProduct;
use App\Models\LowStockNotificationState;
use App\Models\Product;
use App\Models\Recipe;
use App\Models\ShoppingListItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\StorageLocation;
use App\Models\User;
use App\Notifications\Inventory\ProductLowStockNotification;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class DemoUserSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $user = $this->user('test@example.com', 'Test User');
            $member = $this->user('member@example.com', 'Test Member');
            $household = Household::query()->updateOrCreate(['name' => 'House']);

            $ownerMembership = HouseholdMembership::query()->updateOrCreate(
                [
                    'household_id' => $household->getKey(),
                    'user_id' => $user->getKey(),
                ],
                [
                    'role' => HouseholdRole::Owner,
                    'low_stock_reminders_enabled' => true,
                    'low_stock_reminder_interval_hours' => 24,
                ],
            );

            HouseholdMembership::query()->updateOrCreate(
                [
                    'household_id' => $household->getKey(),
                    'user_id' => $member->getKey(),
                ],
                [
                    'role' => HouseholdRole::Member,
                    'low_stock_reminders_enabled' => true,
                    'low_stock_reminder_interval_hours' => 48,
                ],
            );

            $kitchen = $this->location($household, 'Кухня');
            $pantry = $this->location($household, 'Кладовая');

            $milk = $this->product('Молоко', MeasurementType::Volume);
            $eggs = $this->product('Яйца', MeasurementType::Count);
            $rice = $this->product('Рис', MeasurementType::Mass);
            $pasta = $this->product('Макароны', MeasurementType::Mass);
            $oil = $this->product('Оливковое масло', MeasurementType::Volume);
            $bread = $this->product('Хлеб', MeasurementType::Count);
            $tomatoes = $this->product('Помидоры', MeasurementType::Count);
            $cheese = $this->product('Сыр', MeasurementType::Mass);

            $milkStock = $this->stock($household, $milk, $kitchen, '1000.000', '1500.000');
            $eggStock = $this->stock($household, $eggs, $kitchen, '6.000', '4.000', CarbonImmutable::now()->subHours(3));
            $riceStock = $this->stock($household, $rice, $pantry, '500.000', '2000.000');
            $oilStock = $this->stock($household, $oil, $pantry, '500.000', '350.000', CarbonImmutable::now()->subDay());
            $breadStock = $this->stock($household, $bread, $kitchen, '1.000', '1.000', CarbonImmutable::now()->subHours(8));

            StockMovement::query()->where('household_id', $household->getKey())->delete();

            $this->movement($household, $milkStock, $kitchen, $user, StockMovementType::Purchase, '1.500', MeasurementUnit::Liter, '1500.000', '0.000', '1500.000');
            $this->movement($household, $eggStock, $kitchen, $user, StockMovementType::Purchase, '12.000', MeasurementUnit::Piece, '12.000', '0.000', '12.000');
            $this->movement($household, $eggStock, $kitchen, $user, StockMovementType::Consumption, '8.000', MeasurementUnit::Piece, '-8.000', '12.000', '4.000');
            $this->movement($household, $riceStock, $pantry, $user, StockMovementType::Purchase, '2.000', MeasurementUnit::Kilogram, '2000.000', '0.000', '2000.000');
            $this->movement($household, $oilStock, $pantry, $member, StockMovementType::Purchase, '350.000', MeasurementUnit::Milliliter, '350.000', '0.000', '350.000');
            $this->movement($household, $breadStock, $kitchen, $member, StockMovementType::Purchase, '1.000', MeasurementUnit::Piece, '1.000', '0.000', '1.000');

            $this->shoppingItem($household, $pasta, $user, '500.000');
            $this->shoppingItem($household, $tomatoes, $member, '4.000');
            $this->shoppingItem($household, $cheese, $user, '300.000', CarbonImmutable::now()->subHour());

            HouseholdMessage::query()
                ->where('household_id', $household->getKey())
                ->forceDelete();

            HouseholdMessage::factory()->for($household)->for($user, 'sender')->create([
                'content' => 'Яйца кончаются :(',
                'created_at' => CarbonImmutable::now()->subMinutes(20),
            ]);
            HouseholdMessage::factory()->for($household)->for($member, 'sender')->create([
                'content' => 'okay ill go shopping :)',
                'created_at' => CarbonImmutable::now()->subMinutes(15),
            ]);

            $this->seedRecipes($user, compact(
                'milk',
                'eggs',
                'rice',
                'pasta',
                'oil',
                'tomatoes',
                'cheese'
            ));

            LowStockNotificationState::query()->updateOrCreate(
                [
                    'household_membership_id' => $ownerMembership->getKey(),
                    'household_product_id' => $eggStock->getKey(),
                ],
                ['last_notified_at' => CarbonImmutable::now()->subHours(3)]
            );

            $user->notifications()
                ->where('type', ProductLowStockNotification::class)
                ->delete();

            $user->notifyNow(new ProductLowStockNotification(
                householdUuid: $household->uuid,
                householdName: $household->name,
                productUuid: $eggs->uuid,
                productName: $eggs->name,
                measurementType: $eggs->measurement_type,
                totalQuantity: '4.000',
                threshold: '6.000',
                becameLowAt: CarbonImmutable::now()->subHours(3)
            ), ['database']);
        });
    }

    private function user(string $email, string $name): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            ['name' => $name, 'password' => 'password']
        );
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }

    private function product(string $name, MeasurementType $type): Product
    {
        return Product::query()->updateOrCreate(
            ['name' => $name],
            ['measurement_type' => $type]
        );
    }

    private function location(Household $household, string $name): StorageLocation
    {
        return StorageLocation::query()->updateOrCreate([
            'household_id' => $household->getKey(),
            'name' => $name,
        ]);
    }

    private function stock(
        Household $household,
        Product $product,
        StorageLocation $location,
        string $threshold,
        string $quantity,
        ?CarbonImmutable $lowStockSince = null
    ): HouseholdProduct {
        $householdProduct = HouseholdProduct::query()->updateOrCreate(
            [
                'household_id' => $household->getKey(),
                'product_id' => $product->getKey(),
            ],
            [
                'low_stock_threshold' => $threshold,
                'low_stock_since' => $lowStockSince,
            ],
        );

        Stock::query()->updateOrCreate(
            [
                'household_product_id' => $householdProduct->getKey(),
                'storage_location_id' => $location->getKey(),
            ],
            ['quantity' => $quantity]
        );

        return $householdProduct;
    }

    private function movement(
        Household $household,
        HouseholdProduct $householdProduct,
        StorageLocation $location,
        User $actor,
        StockMovementType $type,
        string $inputQuantity,
        MeasurementUnit $inputUnit,
        string $quantityDelta,
        string $quantityBefore,
        string $quantityAfter
    ): void {
        StockMovement::query()->create([
            'household_id' => $household->getKey(),
            'household_product_id' => $householdProduct->getKey(),
            'product_id' => $householdProduct->product_id,
            'storage_location_id' => $location->getKey(),
            'actor_user_id' => $actor->getKey(),
            'type' => $type,
            'input_quantity' => $inputQuantity,
            'input_unit' => $inputUnit,
            'quantity_delta' => $quantityDelta,
            'quantity_before' => $quantityBefore,
            'quantity_after' => $quantityAfter,
            'product_name' => $householdProduct->product->name,
            'storage_location_name' => $location->name,
            'actor_name' => $actor->name,
        ]);
    }

    private function shoppingItem(
        Household $household,
        Product $product,
        User $addedBy,
        string $quantity,
        ?CarbonImmutable $completedAt = null
    ): void {
        ShoppingListItem::query()->updateOrCreate(
            [
                'household_id' => $household->getKey(),
                'product_id' => $product->getKey(),
            ],
            [
                'added_by_user_id' => $addedBy->getKey(),
                'quantity' => $quantity,
                'completed_at' => $completedAt,
            ]
        );
    }

    private function seedRecipes(User $user, array $products): void
    {
        $this->recipe(
            $user,
            'Рис с яйцом',
            'Простенько быстренько из того что осталось в холодильнике',
            2,
            10,
            20,
            [
                [$products['rice'], '200.000', false, null],
                [$products['eggs'], '2.000', false, null],
                [$products['oil'], '15.000', true, '1 столовая ложка'],
            ],
            [
                'Промойте рис и сварите его до готовности',
                'Обжарьте яйца на небольшом количестве масла',
                'Добавьте рис к яйцам, перемешайте и прогрейте несколько минут',
            ]
        );

        $this->recipe(
            $user,
            'Макароны с помидорами и сыром',
            'Хрючево на каждый день',
            2,
            10,
            20,
            [
                [$products['pasta'], '250.000', false, null],
                [$products['tomatoes'], '2.000', false, null],
                [$products['cheese'], '80.000', true, 'Натереть перед подачей'],
                [$products['oil'], '20.000', true, null],
            ],
            [
                'Отварите макароны согласно инструкции на упаковке',
                'Нарежьте помидоры и прогрейте их на сковороде',
                'Смешайте макароны с помидорами и посыпьте сыром',
            ]
        );
    }

    private function recipe(
        User $user,
        string $title,
        string $description,
        int $servings,
        int $beforeCookingMinutes,
        int $cookingMinutes,
        array $ingredients,
        array $steps
    ): void {
        $recipe = Recipe::query()->updateOrCreate(
            [
                'created_by_user_id' => $user->getKey(),
                'title' => $title,
            ],
            [
                'description' => $description,
                'servings' => $servings,
                'before_cooking_minutes' => $beforeCookingMinutes,
                'cooking_minutes' => $cookingMinutes,
            ]
        );

        $recipe->ingredients()->delete();
        $recipe->steps()->delete();

        foreach ($ingredients as $index => [$product, $quantity, $optional, $note]) {
            $recipe->ingredients()->create([
                'product_id' => $product->getKey(),
                'quantity' => $quantity,
                'position' => $index + 1,
                'is_optional' => $optional,
                'note' => $note,
            ]);
        }

        foreach ($steps as $index => $description) {
            $recipe->steps()->create([
                'position' => $index + 1,
                'description' => $description,
            ]);
        }
    }
}
