<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Custom notification channel that POSTs payloads to an external webhook URL.
 *
 * – Reads the default webhook URL from config('services.webhook.url')
 * – Allows per-user overrides via $notifiable->routeNotificationForWebhook()
 * – Uses HTTP client with timeout + retry (Module 35 pattern)
 * – Wraps the call in rescue() so a webhook outage never blocks other channels
 */
class WebhookChannel
{
    /**
     * Send the given notification via webhook.
     */
    public function send(object $notifiable, Notification $notification): void
    {
        // Only send if the notification exposes a toWebhook() method
        if (! method_exists($notification, 'toWebhook')) {
            return;
        }

        // Resolve URL: per-user override → global config
        $url = $notifiable->routeNotificationFor('webhook', $notification)
            ?? config('services.webhook.url');

        if (empty($url)) {
            return;
        }

        $payload = $notification->toWebhook($notifiable);

        // Wrap in rescue() so webhook failures never block mail / database / broadcast
        rescue(function () use ($url, $payload) {
            $cfg = config('services.webhook');

            Http::timeout($cfg['timeout'] ?? 10)
                ->retry($cfg['retries'] ?? 3, $cfg['retry_ms'] ?? 500)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'User-Agent' => config('app.name', 'Laravel').'/Webhook',
                    'X-Webhook-Secret' => $cfg['secret'] ?? '',
                ])
                ->post($url, $payload)
                ->throw(); // Throw on 4xx/5xx so retry logic kicks in
        }, function (\Throwable $e) use ($url) {
            Log::channel('admin')->error('WebhookChannel: delivery failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);
        });
    }
}
