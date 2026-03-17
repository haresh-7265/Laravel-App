<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TestController;
use App\Http\Middleware\LogRequestMiddleware;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/company/config', [CompanyController::class, 'companyDetails']);

Route::get("/discount-price", [ProductController::class, 'discountPrice']);

Route::get('/test', [TestController::class, 'index'])
    ->middleware(LogRequestMiddleware::class);

Route::get('/pay', [PaymentController::class, 'pay']);

Route::get('/test-service', [TestController::class, 'test']);

Route::get('custom-sp',[TestController::class,'message']);