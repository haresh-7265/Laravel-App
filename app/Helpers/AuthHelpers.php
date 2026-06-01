<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

// ─── Multi-Guard Auth Helpers ──────────────────────────────────────────────

if (! function_exists('current_user')) {
    function current_user(): User|Admin|null
    {
        // if impersonating, return customer
        if (session('impersonate.active')) {
            return Auth::guard('web')->user();
        }

        foreach (['admin', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return Auth::guard($guard)->user();
            }
        }

        return null;
    }
}

if (! function_exists('current_guard')) {
    function current_guard(): ?string
    {
        // if impersonating, behave as customer
        if (session('impersonate.active')) {
            return 'web';
        }

        foreach (['admin', 'web'] as $guard) {
            if (Auth::guard($guard)->check()) {
                return $guard;
            }
        }

        return null;
    }
}

if (! function_exists('is_impersonating')) {
    function is_impersonating(): bool
    {
        return session('impersonate.active', false)
        && Auth::guard('admin')->check() 
        && Auth::guard('web')->check();
    }
}

if (! function_exists('is_admin')) {
    /**
     * Check if the current request is authenticated via the admin guard.
     */
    function is_admin(): bool
    {
        return Auth::guard('admin')->check() && !is_impersonating();
    }
}

if (! function_exists('is_customer')) {
    /**
     * Check if the current request is authenticated as a customer (web guard).
     */
    function is_customer(): bool
    {
        return Auth::guard('web')->check() && !is_admin();
    }
}

if (! function_exists('is_guest')) {
    /**
     * True when neither admin nor customer guard is active.
     */
    function is_guest(): bool
    {
        return ! Auth::guard('admin')->check() && ! Auth::guard('web')->check();
    }
}
