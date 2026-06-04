<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Observers\OrderObserver;
use App\Observers\ProductObserver;
use App\Services\CacheService;
use App\Services\CartService;
use App\Services\CouponService;
use App\Services\OrderService;
use App\Services\ProductService;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class ProductServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton('products', function () {
            return new ProductService;
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

        Blade::if('admin', function () {
            return is_admin();
        });

        Blade::if('customer', function () {
            return is_customer();
        });

        Blade::if('anyauth', function () {
            return !is_guest();
        });

        Blade::directive('currency', function ($amount) {
            return "<?php echo format_price($amount); ?>";
        });

        View::composer(['products._form', 'components.export-filter-popup', 'components.product-filter', 'products.index'], function ($view) {
            $categories = Cache::tags(['products', 'categories'])->remember('categories', now()->addHours(2), fn () => Category::all());
            $view->with('categories', $categories);
        });

        View::composer('partials.navbar', function ($view) {
            $cartService = app(CartService::class);
            $cartCount = is_admin() ? 0 : $cartService->count();
            $view->with('cart_count', $cartCount);
        });

        Paginator::useTailwind();

        Product::observe(ProductObserver::class);
        Order::observe(OrderObserver::class);
    }
}
