<?php

use App\Facades\Products;
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
