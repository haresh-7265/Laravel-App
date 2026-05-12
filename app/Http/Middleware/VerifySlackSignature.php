<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifySlackSignature
{
    /**
     * Maximum allowed age of a Slack request (seconds).
     * Prevents replay attacks.
     */
    private const MAX_AGE_SECONDS = 300; // 5 minutes

    /**
     * Handle an incoming request.
     *
     * Validates the Slack request signature using HMAC-SHA256
     * against the app's signing secret.
     *
     * @see https://api.slack.com/authentication/verifying-requests-from-slack
     */
    public function handle(Request $request, Closure $next): Response
    {
        $signingSecret = config('services.slack.signing_secret');

        if (empty($signingSecret)) {
            return response()->json([
                'error' => 'Slack signing secret is not configured.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        $timestamp = $request->header('X-Slack-Request-Timestamp');
        $signature = $request->header('X-Slack-Signature');

        // ---------------------------------------------------------------
        // 1. Reject if headers are missing
        // ---------------------------------------------------------------
        if (! $timestamp || ! $signature) {
            return response()->json([
                'error' => 'Missing Slack signature headers.',
            ], Response::HTTP_FORBIDDEN);
        }

        // ---------------------------------------------------------------
        // 2. Replay protection — reject requests older than 5 minutes
        // ---------------------------------------------------------------
        if (abs(time() - (int) $timestamp) > self::MAX_AGE_SECONDS) {
            return response()->json([
                'error' => 'Request timestamp is too old.',
            ], Response::HTTP_FORBIDDEN);
        }

        // ---------------------------------------------------------------
        // 3. Compute the expected signature
        // ---------------------------------------------------------------
        $sigBasestring    = "v0:{$timestamp}:{$request->getContent()}";
        $expectedSignature = 'v0=' . hash_hmac('sha256', $sigBasestring, $signingSecret);

        // ---------------------------------------------------------------
        // 4. Constant-time comparison to prevent timing attacks
        // ---------------------------------------------------------------
        if (! hash_equals($expectedSignature, $signature)) {
            return response()->json([
                'error' => 'Invalid Slack signature.',
            ], Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
