<?php

namespace App\Notifications;

use Illuminate\Http\Request;
use Illuminate\Notifications\Slack\BlockKit\Blocks\ContextBlock;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;
use Illuminate\Support\Str;
use Throwable;

class SystemErrorAlert extends BaseNotification
{

    private const SENSITIVE_KEYS = [
        'password', 'password_confirmation',
        'token', 'api_key', 'api_token',
        'access_token', 'refresh_token',
        'secret', 'session', 'cookie',
        '_token', 'csrf', 'authorization',
    ];

    public function __construct(
        private readonly string $exceptionClass,
        private readonly string $exceptionMessage,
        private readonly string $file,
        private readonly int    $line,
        private readonly string $requestUrl,
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Named constructor — extracts primitives from the exception + request
     * so the notification is safely serialisable onto the queue.
     */
    public static function fromException(Throwable $exception, ?Request $request = null): static
    {
        return new static(
            exceptionClass:   get_class($exception),
            exceptionMessage: Str::limit($exception->getMessage(), 500),
            file:             $exception->getFile(),
            line:             $exception->getLine(),
            requestUrl:       $request ? static::sanitiseUrl($request) : 'N/A',
        );
    }

    // -------------------------------------------------------------------------
    // Channels
    // -------------------------------------------------------------------------

    public function via(object $notifiable): array
    {
        return ['slack'];
    }

    // -------------------------------------------------------------------------
    // Slack message
    // -------------------------------------------------------------------------

    public function toSlack(object $notifiable): SlackMessage
    {
        return (new SlackMessage)
            ->text(":rotating_light: System Error — {$this->exceptionClass}")
            ->headerBlock(':red_circle: System Error')
            ->sectionBlock(fn (SectionBlock $b) =>
                $b->text('*A 5xx error occurred in '. app()->environment() .'.*')->markdown()
            )
            ->dividerBlock()
            ->sectionBlock(fn (SectionBlock $b) =>
                $b->text(implode("\n", [
                    "*Exception:* `{$this->exceptionClass}`",
                    "*Message:* {$this->exceptionMessage}",
                    "*File:* `{$this->file}`",
                    "*Line:* {$this->line}",
                    "*URL:* {$this->requestUrl}",
                ]))->markdown()
            )
            ->contextBlock(fn (ContextBlock $b) =>
                $b->text(':clock1: ' . now()->toDateTimeString() . ' | Env: ' . app()->environment())
            );
    }


    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Redact sensitive query-string values. Keys are preserved so the URL
     * structure is readable — only the values are masked.
     */
    private static function sanitiseUrl(Request $request): string
    {
        $sensitiveMap = array_flip(array_map('strtolower', self::SENSITIVE_KEYS));

        $params = collect($request->query())
            ->mapWithKeys(fn ($value, $key) => [
                $key => isset($sensitiveMap[strtolower($key)]) ? '***REDACTED***' : $value,
            ])
            ->all();

        $base = $request->url();

        return $params ? $base . '?' . http_build_query($params) : $base;
    }
}