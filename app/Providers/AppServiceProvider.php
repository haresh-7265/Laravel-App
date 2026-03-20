<?php

namespace App\Providers;

use App\Services\Greeter;
use App\Services\PaymentService;
use App\Services\TestService1;
use App\Services\TestService2;
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

        $this->app->bind(PaymentService::class, function () {
            return new PaymentService();
        });

        $this->app->bind(TestService1::class);

        $this->app->singleton(TestService2::class);

        $this->app->singleton('greeter', function () {
            return new Greeter();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Log::info('AppServiceProvider boot method');

        \Response::macro('success', function ($data = null, $message = 'Success', $code = 200) {
            return \Response::json([
                'status'  => 'success',
                'message' => $message,
                'data'    => $data,
            ], $code);
        });

        \Response::macro('error', function ($message = 'Error',$error = null) {
            return response()->json([
                'status' => false,
                'message' => $message,
                'error' => $error
            ]);
        });

        \View::composer('*', function ($view) {
            $view->with('current_logged_user', auth()->user());
        });

        \View::share('company_name', 'Intern Training App');
    }
}
