<?php

namespace App\Notifications;

use App\Jobs\Middleware\ResilientNotificationMiddleware;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Shared retry count
     */
    public int $tries = 3;

    /**
     * Shared max exceptions
     */
    public int $maxExceptions = 3;

    /**
     * Shared timeout
     */
    public int $timeout = 60;

    /**
     * Shared backoff
     */
    public function backoff(): array
    {
        return [10, 30, 60];
    }

    /**
     * Shared middleware
     */
    public function middleware(): array
    {
        return [
            new ResilientNotificationMiddleware(
                $this->logChannel()
            ),
        ];
    }

    /**
     * Override in child notifications
     */
    protected function logChannel(): string
    {
        return 'slack_errors';
    }

    /**
     * Shared failure handler
     */
    public function failed(Exception $exception): void
    {
        Log::channel($this->logChannel())->critical(
            'Notification permanently failed',
            [
                'notification' => static::class,
                'error' => $exception->getMessage(),
            ]
        );

        $this->fallback($exception);
    }

    protected function fallback(Exception $exception): void
    {
        $fallbackEmail = config('services.slack.fallback_email') ?? config('admin.email');


        $details = [
            'type' => get_class($this),
            'reason' => $exception->getMessage(),
            'timestamp' => now()->toDateTimeString(),
            'payload' => method_exists($this, 'getPayload') ? $this->getPayload() : [],
        ];

        NotificationFacade::route('mail', $fallbackEmail)
            ->notify(new SlackFallbackNotification($details));
    }
}
