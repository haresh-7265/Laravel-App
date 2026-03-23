<?php
use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

// ─── Guest only (redirect to dashboard if already logged in) ───
Route::middleware('guest')->group(function () {
    Route::get('/login',     [AuthController::class, 'showLogin'])->name('login');
    Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
    Route::post('/login',    [AuthController::class, 'login'])->name('login.store');
});

// ─── Authenticated only (redirect to /login if guest) ──────────
Route::middleware('auth')->group(function () {
    Route::get('/dashboard',DashboardController::class)->name('dashboard');
    Route::post('/logout',   [AuthController::class, 'logout'])->name('logout');
});

// ─── Root redirect ──────────────────────────────────────────────
Route::get('/', fn() => redirect()->route('login'));