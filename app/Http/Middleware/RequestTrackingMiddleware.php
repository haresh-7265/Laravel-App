<?php

namespace App\Http\Middleware;

use App\Models\Admin;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequestTrackingMiddleware
{
    /**
     * Handle an incoming request.
     * Injects request_id, user_type, user_id, and ip_address into the request context.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = current_user();

        if ($user instanceof User) {
            $this->trackBrowse($user);
        }
        $this->trackRequest($request, $user);

        $response = $next($request);

        Log::channel('request')->info('Outgoing response', [
            'status' => $response->getStatusCode(),
            'content_type' => $response->headers->get('Content-Type'),
        ]);

        return $response;
    }

    private function trackRequest(Request $request, User|Admin|null $user): void
    {
        $requestId = $request->header('X-Request-ID') ?: (string) Str::uuid();
        $userId = $user?->id ?? null;
        $userType = $user ? class_basename($user) : 'Guest';
        $ipAddress = $request->ip();

        Context::add('request_id', $requestId);
        Context::add('user_type', $userType);
        Context::add('user_id', $userId);
        Context::add('ip_address', $ipAddress);

        Log::channel('request')->info('Incoming request', [
            'method' => $request->method(),
            'path' => $request->path(),
            'url' => $request->fullUrl(),
        ]);
    }

    private function trackBrowse(User $user): void
    {
        $key = 'browse.today:'.now()->toDateString();

        Redis::sadd($key, $user->id);
        Redis::expireat($key, now()->endOfDay()->timestamp);
    }
}
