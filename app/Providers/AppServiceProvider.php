<?php

namespace App\Providers;

use App\Models\Admin;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductReview;
use App\Models\User;
use App\Policies\CartPolicy;
use App\Policies\OrderPolicy;
use App\Policies\ProductPolicy;
use App\Policies\ProfilePolicy;
use App\Policies\ReviewPolicy;
use App\Services\ExternalApiService;
use App\Services\FakeStoreService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {


        $this->app->singleton(FakeStoreService::class);

        $this->app->singleton(ExternalApiService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {

        // ── Named Rate Limiters ─────────────────────────────────────
        $this->configureRateLimiting();

        Response::macro('success', function ($data = null, $message = 'Success', $code = 200) {
            return response()->json([
                'status' => 'success',
                'message' => $message,
                'data' => $data,
            ], $code);
        });

        Response::macro('error', function ($message = 'Error', $error = null) {
            return response()->json([
                'status' => false,
                'message' => $message,
                'error' => $error,
            ]);
        });

        View::composer('*', function ($view) {
            $view->with('current_logged_user', current_user());
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
            DB::listen(function (QueryExecuted $event) use (&$listening) {
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
        DB::whenQueryingForLongerThan(100, function (Connection $connection, QueryExecuted $event) use (&$isLoggingSlowQuery) {

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
                Log::error('Failed to dispatch slow query job: '.$e->getMessage());
            } finally {
                $isLoggingSlowQuery = false;
            }
        });

        Model::preventLazyLoading(! app()->isProduction());

        if ($activeGuard = current_guard()) {
            Auth::shouldUse($activeGuard);
        }
        // Set default user resolver to check active guards
        Auth::resolveUsersUsing(fn () => current_user());

        $this->customiseVerificationEmail();
        $this->customisePasswordResetEmail();

        // Register policies
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Order::class, OrderPolicy::class);
        Gate::policy(ProductReview::class, ReviewPolicy::class);
        Gate::policy(User::class, ProfilePolicy::class);
        Gate::policy(Admin::class, ProfilePolicy::class);
        Gate::policy(Cart::class, CartPolicy::class);

        // Define authorization gates
        Gate::define('view-admin-dashboard', fn ($user) => $user instanceof Admin);
        Gate::define('impersonate-users', fn ($user) => $user instanceof Admin);
        Gate::define('view-analytics', fn ($user) => $user instanceof Admin);
        Gate::define('edit-comment', fn ($u, $c, $p) => $u->id === $c->user_id || $u->id === $p->user_id);

        // Super-admin bypass
        Gate::before(function ($user, string $ability, $model) {
            $modelClass = is_array($model) ? reset($model) : $model;

            $skip = [
                [Cart::class, 'view'],
                [Cart::class, 'checkout'],
                [Product::class, 'waitlist'],
            ];

            foreach ($skip as [$skipModel, $skipAbility]) {
                if (is_a($modelClass, $skipModel, true) && $ability === $skipAbility) {
                    return null;
                }
            }
            if ($user instanceof Admin) {
                return true;
            }
        });

        // Audit log trail
        Gate::after(function ($user, string $ability, $result) {
            Log::info('Gate authorization decision', [
                'user_id' => $user?->id,
                'email' => $user?->email,
                'ability' => $ability,
                'result' => (bool) $result ? 'allowed' : 'denied',
            ]);
        });

        // Enforce strong password rules globally
        Password::defaults(function () {
            return Password::min(12)
                ->mixedCase()
                ->numbers()
                ->symbols()
                ->uncompromised();
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

    private function customiseVerificationEmail(): void
    {
        // ✅ custom signed URL
        VerifyEmail::createUrlUsing(function ($notifiable) {
            return URL::temporarySignedRoute(
                'verification.verify',
                Carbon::now()->addMinutes(60), // 60 min expiry
                [
                    'id' => $notifiable->getKey(),
                    'hash' => sha1($notifiable->getEmailForVerification()),
                ]
            );
        });

        // ✅ custom email template — localised
        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            $locale = $notifiable->preferred_locale        // user's saved locale
                ?? app()->getLocale();                     // fallback to app locale

            return (new MailMessage)
                ->subject(__('auth.verify_email_subject', [], $locale))
                ->greeting(__('auth.verify_greeting', ['name' => $notifiable->name], $locale))
                ->line(__('auth.verify_line_1', [], $locale))
                ->action(__('auth.verify_action', [], $locale), $url)
                ->line(__('auth.verify_line_2', ['minutes' => 60], $locale))
                ->line(__('auth.verify_line_3', [], $locale));
        });
    }

    private function customisePasswordResetEmail(): void
    {
        // ✅ custom reset URL
        ResetPassword::createUrlUsing(function ($notifiable, string $token) {
            $route = $notifiable instanceof Admin ? 'admin.password.reset' : 'password.reset';
            return url(route($route, [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));
        });

        // ✅ custom email template — branded and localised
        ResetPassword::toMailUsing(function ($notifiable, string $token) {
            $locale = $notifiable->preferred_locale ?? app()->getLocale();
            $route = $notifiable instanceof Admin ? 'admin.password.reset' : 'password.reset';
            $url = url(route($route, [
                'token' => $token,
                'email' => $notifiable->getEmailForPasswordReset(),
            ], false));

            return (new MailMessage)
                ->subject(__('auth.reset_email_subject', [], $locale))
                ->greeting(__('auth.reset_greeting', ['name' => $notifiable->name], $locale))
                ->line(__('auth.reset_line_1', [], $locale))
                ->action(__('auth.reset_action', [], $locale), $url)
                ->line(__('auth.reset_line_2', ['minutes' => 15], $locale))
                ->line(__('auth.reset_line_3', [], $locale));
        });
    }
}
