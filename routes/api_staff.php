<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\StaffController;
use Illuminate\Support\Facades\Route;

// Ulasan boleh dibaca tanpa login
Route::get('/coffees/{slug}/reviews', [ReviewController::class, 'index']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/coffees/{slug}/reviews', [ReviewController::class, 'store']);

    // Unduhan PDF
    Route::get('/orders/{code}/receipt', [\App\Http\Controllers\Api\DocController::class, 'receipt']);
    Route::get('/staff/labels', [\App\Http\Controllers\Api\DocController::class, 'labels']);

    // Notifikasi (semua peran)
    Route::get('/notices', [\App\Http\Controllers\Api\NoticeController::class, 'index']);
    Route::get('/notices/unread', [\App\Http\Controllers\Api\NoticeController::class, 'unread']);
    Route::post('/notices/read-all', [\App\Http\Controllers\Api\NoticeController::class, 'readAll']);
    Route::post('/notices/{id}/read', [\App\Http\Controllers\Api\NoticeController::class, 'read']);

    // Roaster dan admin
    Route::get('/staff/queue', [StaffController::class, 'queue']);
    Route::post('/staff/orders/advance', [StaffController::class, 'advanceBulk']);
    Route::get('/staff/stock', [AdminController::class, 'stock']);
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

// Menu admin lainnya (laporan, pelanggan, pengguna, voucher, ulasan, kopi, produk, log, pengaturan)
Route::middleware('auth:sanctum')->prefix('admin')->group(function () {
    $c = \App\Http\Controllers\Api\AdminManageController::class;
    Route::get('/reports', [$c, 'report']);
    Route::get('/reports/export', [$c, 'reportExport']);
    Route::get('/stock/history', [$c, 'stockHistory']);
    Route::post('/stock/coffees/{id}', [$c, 'stockAdjustCoffee']);
    Route::post('/stock/products/{id}', [$c, 'stockAddProduct']);
    Route::get('/customers', [$c, 'customers']);
    Route::get('/users', [$c, 'users']);
    Route::post('/users/{id}/block', [$c, 'block']);
    Route::post('/users/{id}/unblock', [$c, 'unblock']);
    Route::get('/vouchers', [$c, 'vouchers']);
    Route::post('/vouchers', [$c, 'voucherStore']);
    Route::put('/vouchers/{id}', [$c, 'voucherUpdate']);
    Route::post('/vouchers/{id}/toggle', [$c, 'voucherToggle']);
    Route::delete('/vouchers/{id}', [$c, 'voucherDelete']);
    Route::get('/reviews', [$c, 'reviews']);
    Route::post('/reviews/{id}/toggle', [$c, 'reviewToggle']);
    Route::delete('/reviews/{id}', [$c, 'reviewDelete']);
    Route::get('/coffees', [$c, 'coffees']);
    Route::post('/coffees/{id}/toggle', [$c, 'coffeeToggle']);
    Route::get('/products', [$c, 'products']);
    Route::post('/products/{id}/toggle', [$c, 'productToggle']);
    Route::get('/logs', [$c, 'logs']);
    Route::get('/settings', [$c, 'settings']);
    Route::put('/settings', [$c, 'settingsSave']);
});
