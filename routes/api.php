<?php

use App\Http\Controllers\Api\HouseholdController;
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
});
