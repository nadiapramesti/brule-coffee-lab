<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CoffeeController;
use App\Http\Controllers\Api\GearController;
use App\Http\Controllers\Api\OrderController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:6,1');
Route::post('/auth/exchange', [AuthController::class, 'exchange'])->middleware('throttle:10,1');

// Katalog boleh dibuka tanpa login
Route::get('/coffees', [CoffeeController::class, 'index']);
Route::get('/coffees/{slug}', [CoffeeController::class, 'show']);
Route::get('/gear', [GearController::class, 'index']);
Route::get('/gear/{slug}', [GearController::class, 'show']);
Route::get('/help/{page}', [\App\Http\Controllers\Api\HelpController::class, 'show']);

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    Route::get('/orders', [OrderController::class, 'index']);
    Route::post('/orders', [OrderController::class, 'store']);
    Route::get('/orders/{code}', [OrderController::class, 'show']);
    Route::post('/orders/{code}/pay', [OrderController::class, 'pay']);
    Route::post('/orders/{code}/sync', [OrderController::class, 'sync']);
    Route::post('/orders/{code}/cancel', [OrderController::class, 'cancel']);
});
require __DIR__.'/api_account.php';
