<?php

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/products', function (Request $request) {
    $products = Product::all();
    return response()->json([
        'status'=>true,
        'message'=>'Product data fatched successfully',
        'data'=>$products->toArray()
    ]);
});
