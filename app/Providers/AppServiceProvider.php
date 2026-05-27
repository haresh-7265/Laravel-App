<?php

namespace App\Providers;

use App\Listeners\CacheEventListener;
use App\Models\Admin;
use App\Models\User;
use App\Services\ExternalApiService;
use App\Services\FakeStoreService;
use App\Services\Greeter;
use App\Services\PaymentService;
use App\Services\TestService1;
use App\Services\TestService2;
use Illuminate\Cache\Events\CacheHit;
use Illuminate\Cache\Events\CacheMissed;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Gate;

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

        // ── Named Rate Limiters ─────────────────────────────────────
        $this->configureRateLimiting();

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
                    'user_id' => rescue(fn () => current_user()->id, null, false),
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

        // Set default user resolver to check active guards
        Auth::resolveUsersUsing(fn () => current_user());

        // Define authorization gates
        Gate::define('view-admin-dashboard', fn ($user) => $user instanceof Admin);
        Gate::define('manage-products', fn ($user) => $user instanceof Admin);
        Gate::define('manage-orders', fn ($user) => $user instanceof Admin);
        Gate::define('impersonate-users', fn ($user) => $user instanceof Admin);
        Gate::define('view-analytics', fn ($user) => $user instanceof Admin);

        // Super-admin bypass
        Gate::before(function ($user, string $ability) {
            if ($user instanceof Admin) {
                return true;
            }
        });

        // Audit log trail
        Gate::after(function ($user, string $ability, bool $result) {
            Log::info("Gate authorization decision", [
                'user_id' => $user?->id,
                'email' => $user?->email,
                'ability' => $ability,
                'result' => $result ? 'allowed' : 'denied',
            ]);
        });
    }

    /**
     * Configure named rate limiters for the application.
     */
    private function configureRateLimiting(): void
    {
        // ── API: Tier-based rate limits ────────────────────────────────────
        // free = 60/min, pro = 600/min, enterprise = 6000/min
        RateLimiter::for('api', function (Request $request) {
            $user = $request->user();
            $limit = $user?->apiRateLimit() ?? User::API_LIMITS[User::TIER_FREE];
            $key = $user?->id ?? $request->ip();

            return Limit::perMinute($limit)
                ->by($key)
                ->response(fn () => response()->json([
                    'status' => 'error',
                    'message' => 'Too many API requests. Please slow down.',
                    'tier' => $user?->subscription_tier ?? 'free',
                    'limit' => $limit,
                    'retry_after' => RateLimiter::availableIn($key),
                ], 429)->withHeaders([
                    'X-RateLimit-Limit' => $limit,
                    'X-RateLimit-Remaining' => 0, // always 0 on a 429
                    'Retry-After' => RateLimiter::availableIn($key),
                ]));
        });

        // free tier rate limiter
        RateLimiter::for('api-free', function (Request $request) {
            $limit = User::API_LIMITS[User::TIER_FREE];

            return Limit::perMinute($limit)
                ->by(current_user()?->id ?? $request->ip())
                ->response(fn () => response()->json([
                    'status' => 'error',
                    'message' => 'Too many API requests. Please slow down.',
                    'tier' => 'free',
                    'limit' => $limit,
                ], 429));
        });

        // pro tier rate limiter
        RateLimiter::for('api-pro', function (Request $request) {
            $limit = User::API_LIMITS[User::TIER_PRO];

            return Limit::perMinute($limit)
                ->by(current_user()?->id ?? $request->ip())
                ->response(fn () => response()->json([
                    'status' => 'error',
                    'message' => 'Too many API requests. Please slow down.',
                    'tier' => 'pro',
                    'limit' => $limit,
                ], 429));
        });

        // enterprice tier rate limiter
        RateLimiter::for('api-enterprise', function (Request $request) {
            $limit = User::API_LIMITS[User::TIER_ENTERPRISE];

            return Limit::perMinute($limit)
                ->by(current_user()?->id ?? $request->ip())
                ->response(fn () => response()->json([
                    'status' => 'error',
                    'message' => 'Too many API requests. Please slow down.',
                    'tier' => 'enterprise',
                    'limit' => $limit,
                ], 429));
        });

        // ── Login: 5 attempts per 5 minutes, keyed by email + IP ────────────
        // Composite key prevents bypass via proxy rotation.
        RateLimiter::for('login', function (Request $request) {
            return Limit::perMinutes(5, 5)
                ->by('login:'.$request->input('email').'|'.$request->ip())
                ->response(function () use ($request) {
                    // Log to security channel
                    Log::channel('security')->warning('🔒 Login rate limit hit', [
                        'email' => $request->input('email'),
                        'ip' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);

                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'rate-limited',
                            'message' => __('auth.throttle', ['seconds' => 300]),
                        ], 429);
                    }

                    return back()
                        ->withErrors(['email' => __('auth.throttle', ['seconds' => 300])])
                        ->withInput($request->except('password'));
                });
        });

        // ── Password Reset: 3 attempts per hour, keyed by email ─────────────
        RateLimiter::for('password-reset', function (Request $request) {
            return Limit::perHour(3)
                ->by('password-reset:'.$request->input('email', $request->ip()))
                ->response(function () use ($request) {
                    // Log to security channel
                    Log::channel('security')->warning('🔒 Password reset rate limit hit', [
                        'email' => $request->input('email'),
                        'ip' => $request->ip(),
                    ]);

                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'rate-limited',
                            'message' => __('auth.password_reset_throttle'),
                        ], 429);
                    }

                    return back()
                        ->withErrors(['email' => __('auth.password_reset_throttle')])
                        ->withInput();
                });
        });

        // ── Checkout: 10 per minute, keyed by authenticated user ────────────
        RateLimiter::for('checkout', function (Request $request) {
            return Limit::perMinute(10)
                ->by($request->user()?->id ?? $request->ip())
                ->response(function () use ($request) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'rate-limited',
                            'message' => 'Too many checkout attempts. Please wait before trying again.',
                            'retry_after_seconds' => 60,
                        ], 429);
                    }

                    return back()
                        ->with('warning', 'You are placing orders too quickly. Please wait a moment before trying again.')
                        ->withInput();
                });
        });

        // ── Search: 30 per minute, keyed by user or IP ──────────────────────
        // Only throttles when a search query is present — normal browsing is unlimited.
        RateLimiter::for('search', function (Request $request) {
            if (! $request->filled('q')) {
                return Limit::none();
            }

            return Limit::perMinute(30)
                ->by($request->user()?->id ?? $request->ip())
                ->response(function () use ($request) {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'error' => 'rate-limited',
                            'message' => 'Search rate limit exceeded. Please slow down.',
                            'retry_after_seconds' => 60,
                        ], 429);
                    }

                    return back()
                        ->with('warning', 'You are searching too quickly. Please wait a moment.')
                        ->withInput();
                });
        });
    }
}
