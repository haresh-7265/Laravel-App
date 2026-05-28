<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\CacheMonitorController;
use App\Http\Controllers\Admin\FileManagerController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ProductImportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SalesAnalyticsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\Customer\DeviceController;
use App\Http\Controllers\FakeStoreController;
use App\Http\Controllers\GithubController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController as CustomerOrderController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\WaitlistController;
use App\Mail\CouponMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('products.index');
});

Route::get('/dashboard', function () {
    return redirect()->route('products.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::prefix('support')->name('support.')->group(function () {
    Route::get('/tickets/create', [SupportTicketController::class, 'create'])
        ->name('tickets.create');
    Route::post('/tickets', [SupportTicketController::class, 'store'])
        ->name('tickets.store');
});

// Contact form (public)
Route::get('contact', [ContactController::class, 'create'])->name('contact.create');
Route::post('contact', [ContactController::class, 'store'])->name('contact.store');

Route::middleware('auth:web,admin')->group(function () {
    Route::middleware('verified')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });
    Route::patch('/profile/locale', [ProfileController::class, 'updateLocale'])->name('profile.locale');

    // ─── Notifications ─────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count', [NotificationController::class, 'unread'])->name('unread');
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('markAllRead');
    });
});

// Admin only (admin guard)
Route::middleware('auth:admin')->group(function () {
    // products route
    Route::get('products/create', [ProductController::class, 'create'])->name('products.create');
    Route::post('products', [ProductController::class, 'store'])->name('products.store');
    Route::get('products/{product}/edit', [ProductController::class, 'edit'])->name('products.edit');
    Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
    Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
    Route::get('products/export', [ProductController::class, 'exportCsv'])->name('products.export');

    // Trashed products (soft-delete management)
    Route::get('products/trashed', [ProductController::class, 'trashed'])->name('products.trashed');
    Route::patch('products/{id}/restore', [ProductController::class, 'restore'])->name('products.restore');
    Route::delete('products/{id}/force-delete', [ProductController::class, 'forceDelete'])->name('products.forceDelete');
    // fakestore products route
    Route::get('/fakestore/products', [FakeStoreController::class, 'index'])->name('fakestore.index');
    // orders route
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
        Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
        Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.updateStatus');
        Route::get('invoices', [AdminOrderController::class, 'invoices'])->name('invoices.index');
        // online customer route
        Route::get('online-customers', function () {
            $customers = User::where('role', 'customer')->orderBy('name')->get();

            return view('admin.browsing', compact('customers'));
        })->name('online-customers');
        // cache performance monitor
        Route::get('cache-monitor', [CacheMonitorController::class, 'index'])->name('cache-monitor');
        Route::post('cache-clear', [CacheMonitorController::class, 'clearAll'])->name('cache-clear');
        // sales route
        Route::get('/sales-analytics', [SalesAnalyticsController::class, 'index'])->name('sales-analytics');
        Route::get('/sales-analytics/export', [SalesAnalyticsController::class, 'exportCsv'])->name('sales-analytics.export');
        Route::prefix('files')->name('files.')->group(function () {
            Route::get('/', [FileManagerController::class, 'index'])->name('index');
            Route::post('/archive', [FileManagerController::class, 'archive'])->name('archive');
            Route::post('/cleanup', [FileManagerController::class, 'cleanup'])->name('cleanup');
            Route::get('/download/{filename}', [FileManagerController::class, 'download'])->name('download');
        });
        // slow query monitor route
        Route::get('slow-queries', function () {
            return view('admin.slow-queries');
        })->name('slow-queries.index');

        // ─── Product CSV Import (Job Batching) ─────────
        Route::get('import', [ProductImportController::class, 'index'])->name('import.index');
        Route::post('import', [ProductImportController::class, 'store'])->name('import.store');
        Route::get('import/status/{batchId}', [ProductImportController::class, 'status'])->name('import.status');
        Route::post('import/cancel/{batchId}', [ProductImportController::class, 'cancel'])->name('import.cancel');

        // Impersonation
        Route::get('impersonate/{id}', [AuthController::class, 'impersonate'])->name('impersonate');
        Route::get('stop-impersonate', [AuthController::class, 'stopImpersonate'])->name('stop-impersonate');

        // User roles manager
        Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
        Route::post('/roles/{user}/assign', [RoleController::class, 'assign'])->name('roles.assign');
        Route::get('/roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
        Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.sync');
    });
});

// Guest
Route::get('products', [ProductController::class, 'index'])
    ->middleware('throttle:search')
    ->name('products.index');
Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('products/search', [ProductController::class, 'search'])->name('products.search');

// Customer routes
Route::middleware(['auth'])->group(function () {
    // varified routes
    Route::middleware('verified')->group(function () {
        Route::get('checkout', [CustomerOrderController::class, 'checkout'])->name('orders.checkout');
        Route::post('orders', [CustomerOrderController::class, 'store'])
            ->middleware('throttle:checkout')
            ->name('orders.store');
        Route::get('my-orders', [CustomerOrderController::class, 'index'])->name('orders.index');
        Route::get('my-orders/{order}', [CustomerOrderController::class, 'show'])->name('orders.show');
        Route::patch('my-orders/{order}/cancel', [CustomerOrderController::class, 'cancel'])->name('orders.cancel');
    });

    Route::get('invoices/{order}/download', [CustomerOrderController::class, 'downloadInvoice'])->name('invoices.download');
    Route::post('products/{product}/waitlist', [WaitlistController::class, 'store'])->name('product.waitlist.store');
    Route::delete('products/{product}/waitlist', [WaitlistController::class, 'destroy'])->name('product.waitlist.destroy');
    Route::post('products/{product}/reviews', [ReviewController::class, 'store'])->name('products.reviews.store');
    Route::delete('products/{product}/reviews/{review}', [ReviewController::class, 'destroy'])->name('products.reviews.destroy');

    Route::get('/customer/devices', [DeviceController::class, 'index'])->name('customer.devices');
    Route::delete('/customer/devices/{id}', [DeviceController::class, 'revoke'])->name('customer.devices.revoke');
});

require __DIR__.'/auth.php';
require __DIR__.'/cart.php';

// ─── Locale Switcher ───────────────────────────────
Route::post('/locale', [LocaleController::class, 'switch'])->name('locale.switch');

Route::get('res-string', function () {
    return 'String Response';
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

Route::get('welcome', function () {
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

    return 'User unsubscribed successfully';
})->name('unsubscribe');

// Display session data
Route::get('/session-data', function () {
    return session()->all(); // shows all session data
});

// Coupon Email preview

Route::get('/preview/coupon-mail', function () {
    $user = User::whereHas('coupons')->inRandomOrder()->firstOrFail();

    $coupon = $user->coupons()
        ->withPivot('usage_limit')
        ->inRandomOrder()
        ->firstOrFail();

    $usageLimit = $coupon->pivot->usage_limit;

    return new CouponMail($user, $coupon, $usageLimit);
});

Route::get('/payment-webhook', PaymentWebhookController::class);

// ─── Github API ────────────────────────────────────

Route::prefix('github')->group(function () {
    Route::get('profile', [GithubController::class, 'profile']);
    Route::get('repos', [GithubController::class, 'repos']);
    Route::get('user/{name}', [GithubController::class, 'user']);
    Route::get('broken', [GithubController::class, 'broken']);
});

// Local environment routes

if (app()->environment('local')) {

    Route::get('/preview/order-confirmation/{order?}', function (?\App\Models\Order $order = null) {
        $order = $order ?? \App\Models\Order::latest()->first();

        return (new \App\Mail\OrderConfirmation($order))->render();
    });

    Route::get('/slack-request', function () {
        $response = Http::post(config('services.slack.webhooks.orders'), [
            'text' => 'Hello from Laravel',
        ]);

        // Check
        return $response->successful();
    });

    Route::get('/test-db', [AnalyticsController::class, 'index']);
}

// Magic login routes
Route::get('/magic-login/{id}', [AuthController::class, 'magicLogin'])->name('magic-login');
Route::get('/generate-magic-link/{id}', [AuthController::class, 'generateMagicLink'])->name('magic-link.generate');
