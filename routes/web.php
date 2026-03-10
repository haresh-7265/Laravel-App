<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/company/config',[CompanyController::class,'companyDetails']);

Route::get("/discount-price",[ProductController::class,'discountPrice']);
