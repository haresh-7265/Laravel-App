<?php

use App\Http\Controllers\CompanyController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\TestController;
use App\Http\Middleware\LogRequestMiddleware;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
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

Route::get('custom-sp', [TestController::class, 'message']);

Route::get('/facade-test', function () {
    Cache::put('name', 'laravel-12');
    $name = Cache::get('name', 'laravel-app');
    Log::info("Cache Value: " . $name);

    $users = DB::table('users')->get();
    $userCount = count($users);

    File::put(storage_path('app/test.txt'), 'File facade');
    $content = File::get(storage_path('app/test.txt'));

    return [
        'name' => $name,
        'usersCount' => $userCount,
        'fileContent' => $content
    ];
});

Route::get('/greet', function () {
    return Greeter::greet("Haresh Ayar");
});

Route::get('/hello', function () {
    return 'Hello World!';
});

Route::post('/submit', function () {
    return 'Form Submitted!';
});

Route::get('/user/{id}', function (string $id) {
    return "User ID is: {$id}";
});

Route::get('/user/{name?}', function (string $name = 'Guest') {
    return "Hello, {$name}!";
});

Route::get('/dashboard', function () {
    return 'Dashboard';
})->name('dashboard');

Route::redirect('/home', '/dashboard');

Route::group([], function () {
    Route::get('/profile', function () {
        return 'Profile';
    });
    Route::get('/settings', function () {
        return 'Settings';
    });
});

Route::prefix('admin')->group(function () {
    Route::get('/dashboard', function () {
        return 'Admin Dashboard';
    });
    Route::get('/users', function () {
        return 'Admin Users';
    });
});

Route::middleware([])->group(function () {
    Route::get('/dashboard1', function () {
        return 'Dashboard 1';
    });
    Route::get('/profile1', function () {
        return 'Profile 1';
    });
});

Route::fallback(function () {
    return 'Page Not Found!';
});