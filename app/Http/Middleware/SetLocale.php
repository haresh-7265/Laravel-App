<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
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
     * Read the locale stored in the session and apply it.
     * Falls back to the config default when the session is empty.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = session('locale', config('app.locale'));

        // Guard against unsupported / tampered values
        if (! in_array($locale, self::SUPPORTED, true)) {
            $locale = config('app.fallback_locale');
        }

        App::setLocale($locale);

        return $next($request);
    }
}
