<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract the plain key from Authorization header, X-API-Key header, or query string
        $plainKey = $request->bearerToken() 
            ?: $request->header('X-API-Key') 
            ?: $request->query('api_key');

        if (!$plainKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'API Key is missing.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Fast, unsalted SHA-256 hash computation
        $hashedIncoming = hash('sha256', $plainKey);

        // Retrieve key record from DB
        $apiKeyRecord = ApiKey::where('key_hash', $hashedIncoming)->first();

        // Perform constant-time verification using hash_equals
        if (!$apiKeyRecord || !hash_equals($apiKeyRecord->key_hash, $hashedIncoming)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API Key.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Authenticate the user for this request
        auth()->login($apiKeyRecord->user);

        // Update usage log
        $apiKeyRecord->update([
            'last_used_at' => now(),
        ]);

        return $next($request);
    }
}
