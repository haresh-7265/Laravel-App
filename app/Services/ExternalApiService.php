<?php

namespace App\Services;

use App\Exceptions\ExternalApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Centralised HTTP client for external API calls.
 *
 * All credentials and tuning knobs come from config('services.external_api'),
 * which in turn reads from .env — nothing is hardcoded.
 */
class ExternalApiService
{
    protected string $baseUrl;
    protected string $token;
    protected int $timeout;
    protected int $retries;
    protected int $retryMs;

    public function __construct()
    {
        $cfg = config('services.external_api');

        $this->baseUrl = rtrim($cfg['base_url'], '/');
        $this->token = $cfg['token'] ?? '';
        $this->timeout = $cfg['timeout'] ?? 30;
        $this->retries = $cfg['retries'] ?? 3;
        $this->retryMs = $cfg['retry_ms'] ?? 500;
    }

    /**
     * Build a pre-configured HTTP client (PendingRequest).
     *
     * Usage:
     *   $response = $this->client()->get('/products');
     *   $response = $this->client()->post('/orders', $payload);
     */
    public function client(): PendingRequest
    {
        $request = Http::baseUrl($this->baseUrl)
            ->timeout($this->timeout)
            ->retry($this->retries, $this->retryMs)
            ->withHeaders([
                'Accept' => 'application/vnd.github+json',
                'Content-Type' => 'application/json',
                'User-Agent' => 'Laravel-App',
            ]);

        // Attach bearer token only when one is configured
        if ($this->token !== '') {
            $request = $request->withToken($this->token);
        }

        return $request;
    }

    // ─── Core request wrapper ──────────────────────────────────────────────
    protected function request(callable $call, string $context): mixed
    {
        try {
            $response = $call();

            // Auto-throw on 4xx/5xx
            $response->throw();

            // Conditional throw — e.g. API returns 200 but body signals error
            $response->throwIf(
                isset($response->json()['error']) && $response->json()['error'] === true,
                'API returned an error in response body'
            );

            return $response->json();

        } catch (RequestException $e) {
            // Bad response — 4xx/5xx
            $status = $e->response?->status() ?? 0;

            Log::error('ExternalApiService: RequestException', [
                'context' => $context,
                'status' => $status,
                'body' => $e->response?->body(),
                'message' => $e->getMessage(),
            ]);

            throw new ExternalApiException(
                message: $this->friendlyMessage($status),
                context: $context,
                apiStatusCode: $status,
                previous: $e
            );

        } catch (ConnectionException $e) {
            // Server unreachable / timeout
            Log::error('ExternalApiService: ConnectionException', [
                'context' => $context,
                'message' => $e->getMessage(),
            ]);

            throw new ExternalApiException(
                message: 'Could not connect to external service. Please try again later.',
                context: $context,
                apiStatusCode: 0,
                previous: $e
            );
        }
    }

    // ─── Public API methods ────────────────────────────────────────────────
    /**
     * GET request to the external API.
     */
    public function get(string $uri, array $query = []): mixed
    {
        return $this->request(
            fn() => $this->client()->get($uri, $query),
            "GET {$uri}"
        );
    }

    /**
     * POST request to the external API.
     */
    public function post(string $uri, array $data = []): mixed
    {
        return $this->request(
            fn() => $this->client()->post($uri, $data),
            "POST {$uri}"
        );
    }

    /**
     * PUT request to the external API.
     */
    public function put(string $uri, array $data = []): mixed
    {
        return $this->request(
            fn() => $this->client()->put($uri, $data),
            "PUT {$uri}"
        );
    }

    /**
     * DELETE request to the external API.
     */
    public function delete(string $uri, array $data = []): mixed
    {
        return $this->request(
            fn() => $this->client()->delete($uri, $data),
            "DELETE {$uri}"
        );
    }

    // ─── Helpers ──────────────────────────────────────────────────────────

    private function friendlyMessage(int $status): string
    {
        return match (true) {
            $status === 401 => 'Authentication failed. Please check API credentials.',
            $status === 403 => 'Access denied by external service.',
            $status === 404 => 'The requested resource was not found.',
            $status === 422 => 'Invalid data sent to external service.',
            $status === 429 => 'Rate limit reached. Please slow down.',
            $status >= 500 => 'External service is currently unavailable.',
            default => 'An unexpected error occurred with the external service.',
        };
    }
}
