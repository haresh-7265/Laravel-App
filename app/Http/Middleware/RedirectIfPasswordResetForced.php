<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfPasswordResetForced
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->force_password_reset) {
            // Exclude profile edit, password update route, and logout route
            if (!$request->routeIs('password.show') &&
                !$request->routeIs('password.update') &&
                !$request->routeIs('logout')) {
                
                return redirect()->route('password.show')
                    ->with('warning', 'You must reset your password before continuing.');
            }
        }

        return $next($request);
    }
}
