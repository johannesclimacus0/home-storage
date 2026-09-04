<?php

use App\Http\Controllers\Api\HouseholdController;
use App\Http\Controllers\Api\HouseholdMessageController;
use App\Http\Controllers\Api\HouseholdProductController;
use App\Http\Controllers\Api\HouseholdRecipeController;
use App\Http\Controllers\Api\LowStockProductController;
use App\Http\Controllers\Api\LowStockReminderSettingController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\RecipeController;
use App\Http\Controllers\Api\RecipeIngredientController;
use App\Http\Controllers\Api\RecipeNoteController;
use App\Http\Controllers\Api\RecipeStepController;
use App\Http\Controllers\Api\ShoppingListItemController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\StorageLocationController;
use App\Http\Controllers\Api\TelegramConnectionController;
use App\Http\Controllers\Api\TelegramLinkController;
use App\Http\Controllers\Api\TelegramReminderController;
use App\Http\Controllers\Api\TelegramSubscriptionController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware(['auth:sanctum', 'throttle:authenticated-api']);

Route::middleware(['auth:sanctum', 'verified', 'throttle:authenticated-api'])->group(function () {
    Route::apiResource('households', HouseholdController::class)
        ->middlewareFor(['store', 'update', 'destroy'], 'throttle:write-operations');

    Route::get('/households/{household}/members', [HouseholdController::class, 'members']);
    Route::post('/households/{household}/members', [HouseholdController::class, 'storeMember'])
        ->middleware('throttle:write-operations');
    Route::delete('/households/{household}/members/{member}', [HouseholdController::class, 'removeMember'])
        ->middleware('throttle:write-operations');
    Route::delete('/households/{household}/membership', [HouseholdController::class, 'leave'])
        ->middleware('throttle:write-operations');
    Route::patch('/households/{household}/owner', [HouseholdController::class, 'transferOwnership'])
        ->middleware('throttle:write-operations');

    Route::apiResource('households.storage-locations', StorageLocationController::class)
        ->parameters(['storage-locations' => 'storageLocation'])
        ->middlewareFor(['store', 'update', 'destroy'], 'throttle:write-operations');

    Route::apiResource('households.products', HouseholdProductController::class)
        ->middlewareFor(['store', 'update', 'destroy'], 'throttle:write-operations');
    Route::get('/households/{household}/low-stock-products', [LowStockProductController::class, 'index']);
    Route::patch('/households/{household}/low-stock-reminder-settings', [LowStockReminderSettingController::class, 'update'])
        ->middleware('throttle:write-operations');

    Route::post('/households/{household}/products/{product}/stocks', [StockController::class, 'store'])
        ->middleware('throttle:write-operations');
    Route::post('/households/{household}/products/{product}/consume', [StockController::class, 'consume'])
        ->middleware('throttle:write-operations');
    Route::get('/households/{household}/stock-movements', [StockMovementController::class, 'index']);

    Route::get('/households/{household}/shopping-list-items', [ShoppingListItemController::class, 'index']);
    Route::post('/households/{household}/shopping-list-items', [ShoppingListItemController::class, 'store'])
        ->middleware('throttle:write-operations');
    Route::patch('/households/{household}/shopping-list-items/{shoppingListItem}', [ShoppingListItemController::class, 'update'])
        ->middleware('throttle:write-operations');
    Route::patch('/households/{household}/shopping-list-items/{shoppingListItem}/complete', [ShoppingListItemController::class, 'complete'])
        ->middleware('throttle:write-operations');
    Route::patch('/households/{household}/shopping-list-items/{shoppingListItem}/reopen', [ShoppingListItemController::class, 'reopen'])
        ->middleware('throttle:write-operations');
    Route::post('/households/{household}/shopping-list-items/{shoppingListItem}/purchase', [ShoppingListItemController::class, 'purchase'])
        ->middleware('throttle:write-operations');
    Route::delete('/households/{household}/shopping-list-items/{shoppingListItem}', [ShoppingListItemController::class, 'destroy'])
        ->middleware('throttle:write-operations');

    Route::get('/households/{household}/messages', [HouseholdMessageController::class, 'index']);
    Route::post('/households/{household}/messages', [HouseholdMessageController::class, 'store'])
        ->middleware('throttle:chat-messages');
    Route::patch('/households/{household}/messages/{message}', [HouseholdMessageController::class, 'update'])
        ->middleware('throttle:write-operations');
    Route::delete('/households/{household}/messages/{message}', [HouseholdMessageController::class, 'destroy'])
        ->middleware('throttle:write-operations');

    Route::get('/households/{household}/recipes', [HouseholdRecipeController::class, 'index']);
    Route::get('/households/{household}/recipes/{recipe}/availability', [HouseholdRecipeController::class, 'show']);
    Route::post('/households/{household}/recipes/{recipe}/shopping-list-items', [HouseholdRecipeController::class, 'addMissingToShoppingList'])
        ->middleware('throttle:write-operations');

    Route::apiResource('products', ProductController::class)
        ->only(['index', 'store', 'show'])
        ->parameters(['products' => 'product'])
        ->middlewareFor('store', 'throttle:write-operations');

    Route::apiResource('recipes', RecipeController::class)
        ->middlewareFor(['store', 'update', 'destroy'], 'throttle:write-operations');
    Route::apiResource('recipes.ingredients', RecipeIngredientController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('throttle:write-operations');
    Route::apiResource('recipes.steps', RecipeStepController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('throttle:write-operations');
    Route::apiResource('recipes.notes', RecipeNoteController::class)
        ->parameters(['notes' => 'recipeNote'])
        ->middlewareFor(['store', 'update', 'destroy'], 'throttle:write-operations');

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])
        ->middleware('throttle:write-operations');
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead'])
        ->middleware('throttle:write-operations');

    Route::get('/telegram/connection', [TelegramConnectionController::class, 'show']);
    Route::delete('/telegram/connection', [TelegramConnectionController::class, 'destroy'])
        ->middleware('throttle:write-operations');
    Route::patch('/telegram/timezone', [TelegramConnectionController::class, 'updateTimezone'])
        ->middleware('throttle:write-operations');
    Route::post('/telegram/link', [TelegramLinkController::class, 'store'])
        ->middleware('throttle:write-operations');
    Route::get('/telegram/subscriptions', [TelegramSubscriptionController::class, 'index']);
    Route::put('/telegram/subscriptions', [TelegramSubscriptionController::class, 'update'])
        ->middleware('throttle:write-operations');
    Route::get('/telegram/reminders', [TelegramReminderController::class, 'index']);
    Route::post('/telegram/reminders', [TelegramReminderController::class, 'store'])
        ->middleware('throttle:write-operations');
    Route::patch('/telegram/reminders/{reminder}', [TelegramReminderController::class, 'update'])
        ->middleware('throttle:write-operations');
    Route::delete('/telegram/reminders/{reminder}', [TelegramReminderController::class, 'destroy'])
        ->middleware('throttle:write-operations');
});
