<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\CacheService;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\OrderService;
use App\Services\ProductService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class ProductServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('products', function () {
            return new ProductService();
        });

        $this->app->singleton(CartService::class);

        $this->app->singleton(OrderService::class);

        $this->app->singleton(CouponService::class);

        $this->app->singleton(CacheService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {

        if (\App::environment('local', 'development')) {
            Log::channel('db-query')->info('===============================================');
            $listening = false; // ✅ flag to prevent re-entry

            DB::listen(function ($query) use (&$listening) {
                if ($listening)
                    return; // ✅ skip if already logging

                $listening = true;

                Log::channel('db-query')->info('DB Query', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'user_id' => auth()->check() ? auth()->id() : 'guest',
                    'time' => $query->time . 'ms',
                ]);

                $listening = false;
            });
        }

        \Blade::directive('admin', function () {
            return "<?php if(auth()->check() && auth()->user()->role === 'admin'): ?>";
        });

        \Blade::directive('endadmin', function () {
            return "<?php endif; ?>";
        });

        \Blade::directive('currency', function ($amount) {
            return "<?php echo config('admin.currency') .' '. number_format((float)$amount, 2); ?>";
        });

        \View::composer(['products._form', 'components.export-filter-popup'], function ($view) {
            $categories = Cache::remember('categories', now()->addHours(2), fn() => Category::all());
            $view->with('categories', $categories);
        });

        \View::composer('partials.navbar', function ($view) {
            $cartService = app(CartService::class);
            $user = auth()->user();
            $cartCount = 0;
            if (!$user || $user->hasRole('customer')) {
                $cartCount = $cartService->count();
            }
            $view->with('cart_count', $cartCount);
        });

        Paginator::useBootstrapFive();
    }
}
