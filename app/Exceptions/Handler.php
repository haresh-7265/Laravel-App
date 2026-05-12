<?php

namespace App\Exceptions;

use App\Notifications\SystemErrorAlert;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Throwable;

class Handler extends ExceptionHandler
{
    public function report(Throwable $e): void
    {
        parent::report($e);

        $this->sendSlackAlert($e);
    }

    private function sendSlackAlert(Throwable $e): void
    {
        $status = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        if ($status < 500) {
            return;
        }

        $cacheKey = 'system_error_alert:'.md5(get_class($e).$e->getFile().$e->getLine());

        Cache::remember($cacheKey, now()->addMinutes(10), function () use ($e): true {
            try {
                Notification::route('slack', config('services.slack.webhooks.errors'))
                    ->notify(SystemErrorAlert::fromException($e, request()));
            } catch (Throwable $notifyException) {
                Log::error('Failed to send SystemErrorAlert', [
                    'original' => get_class($e),
                    'error' => $notifyException->getMessage(),
                ]);
            }

            return true;
        });
    }
}
