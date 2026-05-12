<?php

use App\Facades\Products;
use App\Http\Controllers\SlackInteractionController;
use App\Http\Middleware\VerifySlackSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/products', function (Request $request) {
        $products = Products::getAll();
        return response()->json([
            'status' => true,
            'message' => 'Product data fatched successfully',
            'data' => $products->toArray()
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
