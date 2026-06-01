<?php

use App\Http\Controllers\Admin\Auth\AdminAuthController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin Authentication Routes
|--------------------------------------------------------------------------
|
| These routes handle admin login/logout using the 'admin' guard.
| Admin sessions are fully independent from customer (web) sessions.
|
*/

Route::prefix('admin')->group(function () {

    // Guest-only (not logged in as admin)
    Route::middleware('guest:admin')->group(function () {
        Route::get('login', [AdminAuthController::class, 'showLoginForm'])
            ->name('admin.login');

        Route::post('login', [AdminAuthController::class, 'login'])
            ->middleware('throttle:login')
            ->name('admin.login.submit');
        
        Route::get('forgot-password', [AdminAuthController::class, 'showPasswordResetForm'])->name('admin.password.request');

        Route::post('forgot-password', [AdminAuthController::class, 'sendPasswordResetLink'])
        ->middleware('throttle:password-reset')
        ->name('admin.password.email');

        Route::get('reset-password/{token}', [AdminAuthController::class, 'showNewPasswordForm'])
        ->name('admin.password.reset');

        Route::post('reset-password', [AdminAuthController::class, 'storeNewPassword'])
        ->name('admin.password.store');
    });

    // Authenticated admin
    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])
            ->name('admin.logout');
    });
});
