<?php

namespace App\Listeners;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class LogFailedJob
{
    /**
     * Handle the JobFailed event.
     *
     * Centralised failure reporter that:
     *  1. Logs every failed job to the dedicated channel
     *  2. Posts a Slack notification to the #errors channel (Module 39 pattern)
     */
    public function handle(JobFailed $event): void
    {
        $jobName = $this->resolveJobName($event);
        $error   = Str::limit($event->exception->getMessage(), 300);

        // ── 1. Log to dedicated channel ─────────────────────────────
        Log::channel('order')->error("🔴 Job Failed [{$jobName}]", [
            'connection' => $event->connectionName,
            'queue'      => $event->job->getQueue(),
            'error'      => $error,
            'file'       => $event->exception->getFile(),
            'line'       => $event->exception->getLine(),
        ]);

        // ── 2. Post to Slack #errors via webhook (Module 39) ────────
        $webhookUrl = config('services.slack.webhooks.errors');

        if (! $webhookUrl) {
            return;
        }

        rescue(fn () => Http::post($webhookUrl, [
            'blocks' => [
                [
                    'type' => 'header',
                    'text' => ['type' => 'plain_text', 'text' => '🔴 Job Failed', 'emoji' => true],
                ],
                [
                    'type' => 'section',
                    'text' => [
                        'type' => 'mrkdwn',
                        'text' => implode("\n", [
                            "*Job:* `{$jobName}`",
                            "*Queue:* `{$event->job->getQueue()}`",
                            "*Error:* {$error}",
                            "*Connection:* {$event->connectionName}",
                        ]),
                    ],
                ],
                [
                    'type'     => 'context',
                    'elements' => [
                        [
                            'type' => 'mrkdwn',
                            'text' => ':clock1: ' . now()->toDateTimeString() . ' | Env: ' . app()->environment(),
                        ],
                    ],
                ],
            ],
        ]), fn () => Log::channel('slack_errors')->warning("Failed to send Slack alert for job: {$jobName}"));
    }

    /**
     * Extract a human-readable job class name from the event payload.
     */
    private function resolveJobName(JobFailed $event): string
    {
        $payload = $event->job->payload();

        $displayName = $payload['displayName'] ?? null;

        if ($displayName) {
            return class_basename($displayName);
        }

        return class_basename($payload['job'] ?? 'UnknownJob');
    }
}
