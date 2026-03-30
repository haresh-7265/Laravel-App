<?php

use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('products/search', [ProductController::class, 'search']);
    Route::get('/products/export', [ProductController::class, 'exportCsv'])->name('products.export');
    Route::resource('products', ProductController::class);
});

require __DIR__ . '/auth.php';
require __DIR__ . '/cart.php';

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

Route::get('welcome', function() {
    return view('welcome');
});

// Generate signed URL
Route::get('/test-signed/{user?}', function ($user = 1) {
    $signedUrl = URL::temporarySignedRoute(
        'unsubscribe',
        now()->addMinutes(10), // expires in 10 minutes
        ['user' => $user]
    );
    return "<a href='$signedUrl'> $signedUrl</a>";
});

// Validate signed URL
Route::get('/unsubscribe/{user}', function (Request $request, $user) {
    if (! $request->hasValidSignature()) {
        abort(403, 'Invalid or expired link');
    }

    return "User unsubscribed successfully";
})->name('unsubscribe');

// Display session data
Route::get('/session-data', function () {
    return session()->all(); // shows all session data
});