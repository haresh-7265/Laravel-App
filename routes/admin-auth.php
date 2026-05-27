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
    });

    // Authenticated admin
    Route::middleware('auth:admin')->group(function () {
        Route::post('logout', [AdminAuthController::class, 'logout'])
            ->name('admin.logout');
    });
});
