<?php

use App\Http\Controllers\Api\InventoryApiController;
use App\Http\Middleware\EnsureUserIsActive;
use Illuminate\Support\Facades\Route;

/*
 * Lab 6 API endpoints consumed by the dashboard's AJAX polling script.
 * The session/web middleware group is applied so the same logged-in user is
 * recognised on same-origin fetch() calls (no Sanctum token required).
 */
Route::middleware(['web', 'auth', EnsureUserIsActive::class])->group(function () {
    Route::get('dashboard/stats', [InventoryApiController::class, 'stats'])->name('api.dashboard.stats');
    Route::get('products/low-stock', [InventoryApiController::class, 'lowStock'])->name('api.products.low-stock');
    Route::get('products/{product}/stock', [InventoryApiController::class, 'productStock'])->name('api.products.stock')->whereNumber('product');
    Route::get('transactions/recent', [InventoryApiController::class, 'recentTransactions'])->name('api.transactions.recent');
});
