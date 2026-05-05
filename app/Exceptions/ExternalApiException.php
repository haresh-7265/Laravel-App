<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class ExternalApiException extends Exception
{
    public function __construct(
        string $message = 'External API error',
        private readonly string $context = 'unknown',
        private readonly int $apiStatusCode = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }

    public function getContext(): string
    {
        return $this->context;
    }
    public function getApiStatus(): int
    {
        return $this->apiStatusCode;
    }

    /**
     * JSON for API routes, HTML page for web routes
     */
    public function render(Request $request): JsonResponse|Response
    {
        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'error' => true,
                'message' => $this->getMessage(),
                'context' => $this->context,
            ], $this->resolveStatusCode());
        }

        return response()->view('errors.api-error', [
            'message' => $this->getMessage(),
            'context' => $this->context,
        ], $this->resolveStatusCode());
    }

    private function resolveStatusCode(): int
    {
        return match (true) {
            $this->apiStatusCode >= 400 && $this->apiStatusCode < 600 => $this->apiStatusCode,
            $this->apiStatusCode === 0 => 503,
            default => 502,
        };
    }
}