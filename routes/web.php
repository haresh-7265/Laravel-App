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
use App\Http\Controllers\Customer\ApiKeyController;
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

Route::get('/shared-invoice', [CustomerOrderController::class, 'downloadSharedInvoice'])->name('shared-invoice.download');

Route::get('/profile/cancel-email-change/{user}', [ProfileController::class, 'cancelEmailChange'])
    ->name('profile.cancel-email-change')
    ->middleware('signed');

Route::middleware('auth:admin,web')->group(function () {
    Route::middleware('verified')->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });

    // ─── Notifications ─────────────────────────────────
    Route::prefix('notifications')->name('notifications.')->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/unread-count', [NotificationController::class, 'unread'])->name('unread');
        Route::patch('/{id}/read', [NotificationController::class, 'markAsRead'])->name('markAsRead');
        Route::post('/mark-all-read', [NotificationController::class, 'markAllRead'])->name('markAllRead');
    });
});

// Admin only (admin guard)
Route::middleware('auth:admin,web')->group(function () {

    Route::middleware(['permission:manage_products'])->group(function () {
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

        // ─── Product CSV Import (Job Batching) ─────────
        Route::get('products/import', [ProductImportController::class, 'index'])->name('products.import.index');
        Route::post('products/import', [ProductImportController::class, 'store'])->name('products.import.store');
        Route::get('products/import/status/{batchId}', [ProductImportController::class, 'status'])->name('products.import.status');
        Route::post('products/import/cancel/{batchId}', [ProductImportController::class, 'cancel'])->name('products.import.cancel');
    });

    // orders route
    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::middleware(['permission:manage_orders'])->group(function () {
            Route::get('orders', [AdminOrderController::class, 'index'])->name('orders.index');
            Route::get('orders/{order}', [AdminOrderController::class, 'show'])->name('orders.show');
            Route::patch('orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('orders.updateStatus');
            Route::get('invoices', [AdminOrderController::class, 'invoices'])->name('invoices.index');
        });

        // cache performance monitor
        Route::middleware(['permission:manage_cache'])->group(function () {
            Route::get('cache-monitor', [CacheMonitorController::class, 'index'])->name('cache-monitor');
            Route::post('cache-clear', [CacheMonitorController::class, 'clearAll'])->name('cache-clear');
            // slow query monitor route
            Route::get('slow-queries', function () {
                return view('admin.slow-queries');
            })->name('slow-queries.index');
        });

        // sales route
        Route::middleware(['permission:view_reports'])->group(function () {
            Route::get('/sales-analytics', [SalesAnalyticsController::class, 'index'])->name('sales-analytics');
            Route::get('/sales-analytics/export', [SalesAnalyticsController::class, 'exportCsv'])->name('sales-analytics.export');
            Route::prefix('files')->name('files.')->group(function () {
                Route::get('/', [FileManagerController::class, 'index'])->name('index');
                Route::post('/archive', [FileManagerController::class, 'archive'])->name('archive');
                Route::post('/cleanup', [FileManagerController::class, 'cleanup'])->name('cleanup');
                Route::get('/download/{filename}', [FileManagerController::class, 'download'])->name('download');
            });
        });

        Route::middleware(['role:admin'])->group(function () {

            // Impersonation
            Route::get('impersonate/{id}', [AuthController::class, 'impersonate'])->name('impersonate');
            Route::get('stop-impersonate', [AuthController::class, 'stopImpersonate'])->name('stop-impersonate');

            // Magic login routes
            Route::get('/generate-magic-link/{id}', [AuthController::class, 'generateMagicLink'])->name('magic-link.generate');

        });

        // User roles & permission manager
        Route::middleware(['permission:assign_roles'])->group(function () {

            Route::post('/roles/{user}/assign', [RoleController::class, 'assign'])->name('roles.assign');
            Route::get('/roles/{role}/permissions', [RoleController::class, 'permissions'])->name('roles.permissions');
            Route::post('/roles/{role}/permissions', [RoleController::class, 'syncPermissions'])->name('roles.sync');
        });

        Route::middleware(['permission:manage_users'])->group(function () {
            Route::get('/roles', [RoleController::class, 'index'])->name('roles.index');
            Route::post('/roles/{user}/verify', [RoleController::class, 'verifyUser'])->name('roles.verify');
            Route::post('/roles/{user}/unverify', [RoleController::class, 'unverifyUser'])->name('roles.unverify');
            Route::post('/roles/{user}/force-reset', [RoleController::class, 'forcePasswordReset'])->name('roles.force-reset');
            // Soft-delete (available to manage_users)
            Route::delete('/roles/{user}/delete', [RoleController::class, 'destroy'])->name('roles.delete');
            // Trash management — admin role only
            Route::middleware(['role:admin'])->group(function () {
                Route::post('/roles/{id}/restore', [RoleController::class, 'restore'])->name('roles.restore');
                Route::delete('/roles/{id}/force-delete', [RoleController::class, 'forceDelete'])->name('roles.forceDelete');
            });
            // online customer route
            Route::get('online-customers', function () {
                $customers = collect();
                if (current_user()->can('impersonate-users')) {
                    $customers = User::orderBy('name')->get();
                }

                return view('admin.browsing', compact('customers'));
            })->name('online-customers');
        });

    });
});

// Guest
Route::get('products', [ProductController::class, 'index'])
    ->middleware('throttle:search')
    ->name('products.index');
Route::get('products/{product}', [ProductController::class, 'show'])->name('products.show');
Route::get('products/search', [ProductController::class, 'search'])->name('products.search');

// magic login route
Route::get('/magic-login/{id}', [AuthController::class, 'magicLogin'])->name('magic-login');

Route::middleware(['auth'])->group(function () {

    // Customer routes
    Route::middleware(['role:customer'])->group(function () {

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

    });

    Route::get('/user/devices', [DeviceController::class, 'index'])->name('user.devices');
    Route::delete('/user/devices/{id}', [DeviceController::class, 'revoke'])->name('user.devices.revoke');

    // Web API Key Management routes
    Route::get('/api-keys', [ApiKeyController::class, 'indexPage'])->name('user.api-keys');
    Route::get('/api-keys/data', [ApiKeyController::class, 'index'])->name('api-keys.index');
    Route::post('/api-keys', [ApiKeyController::class, 'store'])->name('api-keys.store');
    Route::delete('/api-keys/{id}', [ApiKeyController::class, 'destroy'])->name('api-keys.destroy');
});

require __DIR__.'/auth.php';
require __DIR__.'/cart.php';

// ─── Locale Switcher ───────────────────────────────
Route::patch('/locale', [LocaleController::class, 'switch'])->name('locale.switch');

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
    $user = User::customers()->whereHas('coupons')->inRandomOrder()->firstOrFail();

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
