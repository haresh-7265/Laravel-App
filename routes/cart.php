<?php

use App\Http\Controllers\CartController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth')->prefix('cart')->name('cart.')->group(function () {
    Route::get('/',                      [CartController::class, 'index'])->name('index');
    Route::post('/add/{product}',        [CartController::class, 'add'])->name('add');
    Route::patch('/update/{productId}',  [CartController::class, 'update'])->name('update');
    Route::delete('/remove/{productId}', [CartController::class, 'remove'])->name('remove');
    Route::delete('/clear',              [CartController::class, 'clear'])->name('clear');
});

// Coupon routes — auth required (customers only)
Route::middleware(['auth'])->prefix('cart/coupon')->name('cart.coupon.')->group(function () {
    Route::post('/apply',  [CartController::class, 'applyCoupon'])->name('apply');
    Route::delete('/remove', [CartController::class, 'removeCoupon'])->name('remove');
});