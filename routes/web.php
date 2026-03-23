<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('products/search', [ProductController::class, 'search']);
    Route::resource('products', ProductController::class);
});

require __DIR__ . '/auth.php';

Route::get('res-string', function () {
    return "String Response";
});

Route::get('res-json', function () {
    return response()->json(['message' => 'JSON response']);
});

Route::get('res-array', function () {
    return ['message' => 'array response']; // Laravel auto-converts any Model, Collection, or array returned from a controller to JSON.
});

Route::get('res-view', function () {
    return view('view');
});

Route::get('download-invoice', function () {
    return response()->download(storage_path('app/public/products/Asus slim 15.jpg'), 'invoice');
});