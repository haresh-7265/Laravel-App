<?php

namespace App\Providers;

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
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        \Blade::directive('admin', function () {
            return "<?php if(auth()->user()->role === 'admin'): ?>";
        });

        \Blade::directive('endadmin', function () {
            return "<?php endif; ?>";
        });

        \Blade::directive('currency', function ($amount) {
            return "<?php echo config('admin.currency') .' '. number_format((float)$amount, 2); ?>";
        });
    }
}
