<?php

namespace App\Services;

use App\Mail\LoginWarningMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class AuthThrottleService
{
    protected const LOCK_TIME_MINUTES = 15;

    protected const MAX_ATTEMPTS_LOCK = 5;

    protected const MAX_ATTEMPTS_CAPTCHA = 10;

    // ── guard-aware model resolution ──────────────────
    protected function findUser(string $email, string $guard): mixed
    {
        $provider = config("auth.guards.{$guard}.provider");
        $model = config("auth.providers.{$provider}.model");

        return $model::where('email', $email)->first();
    }

    // ── cache key includes guard ───────────────────────
    protected function cacheKey(string $type, string $email, string $guard): string
    {
        return "login_{$type}:{$guard}:".md5($email);
    }

    /**
     * Check lockout — guard aware.
     */
    public function checkLockout(string $email, string $guard): ?int
    {
        $lockedUntil = Cache::get($this->cacheKey('lock', $email, $guard));

        if (! $lockedUntil) {
            return null;
        }

        $remaining = now()->diffInSeconds($lockedUntil, false);

        if ($remaining > 0) {
            return $remaining;
        }

        Cache::forget($this->cacheKey('lock', $email, $guard));

        return null;
    }

    /**
     * Check CAPTCHA required — guard aware.
     */
    public function requiresCaptcha(string $email, string $guard): bool
    {
        return $this->failureCount($email, $guard) >= self::MAX_ATTEMPTS_CAPTCHA;
    }

    /**
     * Handle failed attempt — guard aware.
     */
    public function handleFailedAttempt(string $email, string $guard, string $ip): void
    {
        $failuresKey = $this->cacheKey('failures', $email, $guard);
        $lockKey = $this->cacheKey('lock', $email, $guard);
        $ttl = now()->addMinutes(self::LOCK_TIME_MINUTES);

        $failures = (int) Cache::get($failuresKey, 0) + 1;
        Cache::put($failuresKey, $failures, $ttl);

        if ($failures >= self::MAX_ATTEMPTS_LOCK) {
            Cache::put($lockKey, $ttl, $ttl);

            // find user via correct model for this guard ✅
            $user = $this->findUser($email, $guard);
            if ($user) {
                try {
                    Mail::to($email)->send(new LoginWarningMail(
                        email: $email,
                        ip: $ip,
                        time: now()->toDayDateTimeString()
                    ));
                } catch (\Exception $e) {
                    Log::channel('security')->error('Failed to send login warning', [
                        'email' => $email,
                        'guard' => $guard,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            Log::channel('security')->warning('Account locked', [
                'email' => $email,
                'guard' => $guard,  // ✅ log which guard
                'ip' => $ip,
                'failures' => $failures,
            ]);
        }
    }

    /**
     * Reset on successful login — guard aware.
     */
    public function resetFailedAttempts(string $email, string $guard): void
    {
        Cache::forget($this->cacheKey('failures', $email, $guard));
        Cache::forget($this->cacheKey('lock', $email, $guard));
    }

    /**
     * Get failure count — guard aware.
     */
    public function failureCount(string $email, string $guard): int
    {
        return (int) Cache::get($this->cacheKey('failures', $email, $guard), 0);
    }

    /**
     * Log attempt — guard aware.
     */
    public function logAttempt(
        string $email,
        string $guard,
        bool $success,
        string $ip,
        string $userAgent,
        ?string $reason = null
    ): void {
        Log::channel('security')->info('Login attempt — '.($success ? 'SUCCESS' : 'FAILURE'), [
            'email' => $email,
            'guard' => $guard,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'reason' => $reason,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
