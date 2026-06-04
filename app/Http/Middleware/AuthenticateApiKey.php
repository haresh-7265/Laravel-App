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

        if (! $plainKey) {
            return response()->json([
                'status' => 'error',
                'message' => 'API Key is missing.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Step 1 — split into public key_id and secret part
        if (! str_contains($plainKey, '.')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API Key format.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        [$keyId, $secret] = explode('.', $plainKey, 2);

        // Step 2 — lookup by key_id ONLY (non-secret, safe to query)
        $apiKeyRecord = ApiKey::where('key_id', $keyId)->first();

        if (! $apiKeyRecord) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API Key.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Step 3 — hash_equals
        if (! hash_equals($apiKeyRecord->key_secret_hash, hash('sha256', $secret))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid API Key.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Step 4 — authenticated
        auth()->login($apiKeyRecord->user);

        // Update usage log
        $apiKeyRecord->update([
            'last_used_at' => now(),
        ]);

        return $next($request);
    }
}
