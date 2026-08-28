<?php

use Illuminate\Support\Facades\Route;

Route::view('/', 'app');
Route::view('/login', 'app');
Route::view('/verify-email', 'app');
Route::view('/register', 'app');
Route::view('/forgot-password', 'app');
Route::view('/reset-password/{token}', 'app');
Route::view('/dashboard', 'app');
Route::view('/products', 'app');
Route::view('/shopping-list', 'app');
Route::view('/locations', 'app');
Route::view('/history', 'app');
Route::view('/catalog', 'app');
Route::view('/households', 'app');
Route::view('/notifications', 'app');
Route::view('/chat', 'app');
