<?php

namespace App\Jobs\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Throwable;

class ResilientNotificationMiddleware
{
    public function __construct(
        protected string $channel = 'default'
    ) {}

    public function handle($job, Closure $next): void
    {
        try {

            $next($job);

        } catch (Throwable $e) {

            $this->logFailure($job, $e);

            /**
             * Handle 429 Retry-After
             */
            $response = method_exists($e, 'response')
                ? $e->response()
                : null;

            if ($response && $response->status() === 429) {

                $retryAfter = $response->header('Retry-After', 60);

                Log::channel($this->channel)->warning('Rate limited', [
                    'job' => get_class($job),
                    'retry_after' => $retryAfter,
                ]);

                $job->release($retryAfter);

                return;
            }

            throw $e;
        }
    }

    protected function logFailure($job, Throwable $e): void
    {
        Log::channel($this->channel)->error('Notification failed', [
            'job' => get_class($job),
            'message' => $e->getMessage(),
        ]);
    }
}
