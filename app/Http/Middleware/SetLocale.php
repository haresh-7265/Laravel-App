<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Number;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Supported locales — add new languages here.
     */
    public const SUPPORTED = ['en', 'ar'];

    /**
     * RTL locales — used by the layout to flip direction.
     */
    public const RTL = ['ar', 'he', 'fa', 'ur'];

    /**
     * Resolve and apply the active locale.
     *
     * Priority chain:
     * 1. Authenticated user's DB preference  (users.preferred_locale column)
     * 2. Session value                       (set by guest language switcher)
     * 3. Config default                      (config/app.php → locale)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->resolveLocale($request);

        // Guard against unsupported / tampered values
        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.fallback_locale');
        }

        // Apply locale to all subsystems
        App::setLocale($locale);
        Carbon::setLocale($locale);
        Number::useLocale($locale);
        Number::useCurrency(config("admin.currency_code.{$locale}.code"));

        return $next($request);
    }

    /**
     * Determine the locale using the priority chain:
     * DB preference → session → config default.
     */
    private function resolveLocale(Request $request): string
    {
        // 1. Authenticated user's saved preference
        if (current_user() && current_user()->preferred_locale) {
            return current_user()->preferred_locale;
        }

        // 2. Session value (guest language switcher)
        // 3. Config default fallback
        return session('locale', config('app.locale'));
    }
}
