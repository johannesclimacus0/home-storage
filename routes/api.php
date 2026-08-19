<?php

use App\Http\Controllers\Api\HouseholdController;
use App\Http\Controllers\Api\HouseholdProductController;
use App\Http\Controllers\Api\StockController;
use App\Http\Controllers\Api\StorageLocationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::middleware(['auth:sanctum', 'verified'])->group(function () {
    Route::post('/households', [HouseholdController::class, 'store']);
    Route::post('/households/{household}/members', [HouseholdController::class, 'storeMember']);
    Route::patch('/households/{household}/owner', [HouseholdController::class, 'transferOwnership']);
    Route::get('/households', [HouseholdController::class, 'index']);
    Route::post('/households/{household}/storage-locations', [StorageLocationController::class, 'store']);
    Route::get('/households/{household}/products', [HouseholdProductController::class, 'index']);
    Route::post('/households/{household}/products', [HouseholdProductController::class, 'store']);
    Route::get('/households/{household}/products/{product}', [HouseholdProductController::class, 'show']);
    Route::patch('/households/{household}/products/{product}', [HouseholdProductController::class, 'update']);
    Route::delete('/households/{household}/products/{product}', [HouseholdProductController::class, 'destroy']);
    Route::post('/households/{household}/products/{product}/stocks', [StockController::class, 'store']);
    Route::post('/households/{household}/products/{product}/consume', [StockController::class, 'consume']);
});
