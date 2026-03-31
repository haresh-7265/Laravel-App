<?php

namespace App\Providers;

use App\Models\Category;
use App\Services\CartService;
use App\Services\ProductService;
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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $cartService = app(CartService::class);

        \Blade::directive('admin', function () {
            return "<?php if(auth()->check() && auth()->user()->role === 'admin'): ?>";
        });

        \Blade::directive('endadmin', function () {
            return "<?php endif; ?>";
        });

        \Blade::directive('currency', function ($amount) {
            return "<?php echo config('admin.currency') .' '. number_format((float)$amount, 2); ?>";
        });

        \View::composer(['products._form','components.export-filter-popup'], function ($view) {
            $view->with('categories', Category::all());
        });

        \View::composer('layouts.app', function ($view) use ($cartService){
            $view->with('cart_count', $cartService->count());
        });
    }
}
