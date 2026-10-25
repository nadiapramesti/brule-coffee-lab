<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\StaffController;
use Illuminate\Support\Facades\Route;

// Ulasan boleh dibaca tanpa login
Route::get('/coffees/{slug}/reviews', [ReviewController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/coffees/{slug}/reviews', [ReviewController::class, 'store']);

    // Roaster dan admin
    Route::get('/staff/queue', [StaffController::class, 'queue']);
    Route::get('/staff/orders/{code}', [StaffController::class, 'show']);
    Route::post('/staff/orders/{code}/advance', [StaffController::class, 'advance']);
    Route::post('/staff/orders/{code}/ship', [StaffController::class, 'ship']);

    // Khusus admin
    Route::get('/admin/dashboard', [AdminController::class, 'dashboard']);
    Route::get('/admin/orders', [AdminController::class, 'orders']);
    Route::get('/admin/stock', [AdminController::class, 'stock']);
    Route::post('/admin/orders/{code}/complete', [AdminController::class, 'complete']);
    Route::post('/admin/orders/{code}/cancel', [AdminController::class, 'cancel']);
});
