<?php

use App\Http\Controllers\Api\HouseholdController;
use App\Http\Controllers\Api\HouseholdProductController;
use App\Http\Controllers\Api\LowStockProductController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StockMovementController;
use App\Http\Controllers\Api\StorageLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::apiResource('households', HouseholdController::class);

    Route::get('/households/{household}/members', [HouseholdController::class, 'members']);
    Route::post('/households/{household}/members', [HouseholdController::class, 'storeMember']);
    Route::delete('/households/{household}/members/{member}', [HouseholdController::class, 'removeMember']);
    Route::delete('/households/{household}/membership', [HouseholdController::class, 'leave']);
    Route::patch('/households/{household}/owner', [HouseholdController::class, 'transferOwnership']);

    Route::apiResource('households.storage-locations', StorageLocationController::class)
        ->parameters(['storage-locations' => 'storageLocation']);

    Route::apiResource('households.products', HouseholdProductController::class);
    Route::get('/households/{household}/low-stock-products', [LowStockProductController::class, 'index']);

    Route::post('/households/{household}/products/{product}/stocks', [StockController::class, 'store']);
    Route::post('/households/{household}/products/{product}/consume', [StockController::class, 'consume']);
    Route::get('/households/{household}/stock-movements', [StockMovementController::class, 'index']);

    Route::apiResource('products', ProductController::class)
        ->only(['index', 'store', 'show'])
        ->parameters(['products' => 'product']);

    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::patch('/notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    Route::patch('/notifications/{notification}/read', [NotificationController::class, 'markAsRead']);
});
