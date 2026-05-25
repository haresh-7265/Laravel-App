<?php

namespace App\Providers;

use App\Listeners\CacheEventListener;
use App\Services\ExternalApiService;
use App\Services\FakeStoreService;
use App\Services\Greeter;
use App\Services\PaymentService;
use App\Services\TestService1;
use App\Services\TestService2;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Log::info('AppServiceProvider register method');

        $this->app->bind(PaymentService::class, function () {
            return new PaymentService;
        });

        $this->app->bind(TestService1::class);

        $this->app->singleton(TestService2::class);

        $this->app->singleton('greeter', function () {
            return new Greeter;
        });

        $this->app->singleton(FakeStoreService::class);

        $this->app->singleton(ExternalApiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Log::info('AppServiceProvider boot method');

        // // ─── Cache event logging ────────────────────────
        // $listener = new CacheEventListener();
        // Event::listen(CacheHit::class, [$listener, 'handleCacheHit']);
        // Event::listen(CacheMissed::class, [$listener, 'handleCacheMissed']);

        \Response::macro('success', function ($data = null, $message = 'Success', $code = 200) {
            return \Response::json([
                'status' => 'success',
                'message' => $message,
                'data' => $data,
            ], $code);
        });

        \Response::macro('error', function ($message = 'Error', $error = null) {
            return response()->json([
                'status' => false,
                'message' => $message,
                'error' => $error,
            ]);
        });

        \View::composer('*', function ($view) {
            $view->with('current_logged_user', auth()->user());
        });

        \View::share('company_name', 'Intern Training App');

        \Blade::directive('currency', function ($amount) {
            return "<?php echo '₹' . number_format((float)$amount, 2); ?>";
        });

        Http::macro('jsonApi', function (string $baseUrl, string $apiKey, int $timeout) {
            return Http::baseUrl($baseUrl)
                ->withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'X-Api-Key' => $apiKey,
                ])
                ->timeout($timeout);
        });

        Str::macro('initials', function ($value, $limit = 2) {
            return collect(preg_split('/\s+/', trim($value)))
                ->filter()
                ->map(fn ($word) => strtoupper($word[0] ?? ''))
                ->take($limit)
                ->join('');
        });

        if (! app()->isProduction()) {
            $listening = false;
            \DB::listen(function (QueryExecuted $event) use (&$listening) {
                if ($listening) {
                    return;
                } // ✅ skip if already logging

                $listening = true;
                Log::channel('db-query')->debug('DB Query', [
                    'sql' => $event->sql,
                    'bindings' => $event->bindings,
                    'time_ms' => $event->time,
                ]);

                $listening = false;
            });
        }

        $isLoggingSlowQuery = false;
        \DB::whenQueryingForLongerThan(100, function (Connection $connection, QueryExecuted $event) use (&$isLoggingSlowQuery) {

            if ($isLoggingSlowQuery) {
                return;
            }
            // Prevent infinite loop — skip logging the analytics connection itself
            if ($connection->getName() === 'analytics') {
                return;
            }

            $isLoggingSlowQuery = true;

            try {
                dispatch(new \App\Jobs\LogSlowQueryJob([
                    'sql' => $event->sql,
                    'bindings' => json_encode($event->bindings),
                    'time_ms' => $event->time,
                    'connection' => $connection->getName(),
                    'url' => rescue(fn () => request()->fullUrl(), null, false),
                    'method' => rescue(fn () => request()->method(), null, false),
                    'ip_address' => rescue(fn () => request()->ip(), null, false),
                    'user_agent' => rescue(fn () => request()->userAgent(), null, false),
                    'user_id' => rescue(fn () => auth()->id(), null, false),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]))->onQueue('analytics'); // Runs AFTER response is sent to user
            } catch (\Throwable $e) {
                \Log::error('Failed to dispatch slow query job: '.$e->getMessage());
            } finally {
                $isLoggingSlowQuery = false;
            }
        });

        Model::preventLazyLoading(! app()->isProduction());
    }
}
