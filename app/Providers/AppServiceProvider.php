<?php

namespace App\Providers;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Log::info('AppServiceProvider register method');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Log::info('AppServiceProvider boot method');
    }
}
