<?php

use App\Facades\Products;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Customer\ApiAuthController;
use App\Http\Controllers\SlackInteractionController;
use App\Http\Middleware\VerifySlackSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:api')->group(function () {
    Route::get('/products', function (Request $request) {
        $products = Products::getAll();

        return response()->json([
            'status' => true,
            'message' => 'Product data fatched successfully',
            'data' => $products->toArray(),
        ]);
    });
});

/*
|--------------------------------------------------------------------------
| Slack Interactive Messages
|--------------------------------------------------------------------------
|
| Handles button clicks from Slack interactive messages (Block Kit).
| Protected by HMAC signature verification middleware.
|
*/
Route::post('slack/interactions', [SlackInteractionController::class, 'handle'])
    ->middleware(VerifySlackSignature::class);

Route::middleware(['web', 'auth:admin'])->get('/orders', [OrderController::class, 'indexApi'])->name('api.orders.index');

// public
Route::post('/login',    [ApiAuthController::class, 'login']);
Route::post('/register', [ApiAuthController::class, 'register']);

// protected
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout',          [ApiAuthController::class, 'logout']);
    Route::get('/tokens',           [ApiAuthController::class, 'tokens']);
    Route::delete('/tokens/{id}',   [ApiAuthController::class, 'revokeToken']);
    Route::get('/profile',          [ApiAuthController::class, 'profile']);
});

// API Key authenticated test route
Route::middleware('auth.apikey')->get('/secure-data', function () {
    return response()->json([
        'status' => 'success',
        'message' => 'You have accessed secure data using a custom API Key.',
        'user' => auth()->user()->only('id', 'name', 'email'),
    ]);
});