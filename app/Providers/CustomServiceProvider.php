<?php

namespace App\Providers;

use App\Services\CustomService;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class CustomServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(CustomService::class, function () {
            return new CustomService();
        });

        config(['message' => "config message value"]);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        
        View::composer('*', function ($view) {
            $view->with('globalMessage', config('message', 'default message'));
        });

    }
}
