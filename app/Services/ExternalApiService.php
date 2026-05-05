<?php

namespace App\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

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

    // ─── Convenience helpers ───────────────────────────────────────────

    /**
     * GET request to the external API.
     */
    public function get(string $uri, array $query = []): mixed
    {
        return $this->client()->get($uri, $query);
    }

    /**
     * POST request to the external API.
     */
    public function post(string $uri, array $data = []): mixed
    {
        return $this->client()->post($uri, $data);
    }

    /**
     * PUT request to the external API.
     */
    public function put(string $uri, array $data = []): mixed
    {
        return $this->client()->put($uri, $data);
    }

    /**
     * DELETE request to the external API.
     */
    public function delete(string $uri, array $data = []): mixed
    {
        return $this->client()->delete($uri, $data);
    }
}
