<?php

namespace App\Providers;

use App\Models\{Category, Order, Product};
use App\Observers\{OrderObserver, ProductObserver};
use App\Services\{CacheService, CartService, CouponService, OrderService, ProductService};
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\{Cache, DB, Log};
use Illuminate\Support\Number;
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

        // Number::useCurrency(config('admin.currency_code'));

        \Blade::directive('admin', function () {
            return "<?php if(auth()->check() && auth()->user()->role === 'admin'): ?>";
        });

        \Blade::directive('endadmin', function () {
            return "<?php endif; ?>";
        });

        \Blade::directive('currency', function ($amount) {
            return "<?php echo format_price($amount); ?>";
        });

        \View::composer(['products._form', 'components.export-filter-popup', 'components.product-filter','products.index'], function ($view) {
            $categories = Cache::tags(['products', 'categories'])->remember('categories', now()->addHours(2), fn() => Category::all());
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

        Product::observe(ProductObserver::class);
        Order::observe(OrderObserver::class);
    }
}
