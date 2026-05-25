<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Slack\BlockKit\Blocks\ContextBlock;
use Illuminate\Notifications\Slack\BlockKit\Blocks\SectionBlock;
use Illuminate\Notifications\Slack\SlackMessage;

class JobFailedAlert extends BaseNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly string $jobName,
        public readonly string $errorMessage,
        public readonly array $context = [],
    ) {
        $this->onQueue('notifications');
    }

    /**
     * Delivery channels: mail + database + slack.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'slack'];
    }

    /**
     * Route mail and slack to the "emails" queue.
     */
    public function viaQueues(): array
    {
        return [
            'mail'  => 'emails',
            'slack' => 'emails',
        ];
    }

    /**
     * Get the mail representation.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("🔴 Job Failed: {$this->jobName}")
            ->greeting("Job Failure Alert")
            ->line("The job **{$this->jobName}** has permanently failed after all retry attempts.")
            ->line("**Error:** {$this->errorMessage}");

        foreach ($this->context as $key => $value) {
            $mail->line("**" . ucfirst(str_replace('_', ' ', $key)) . ":** {$value}");
        }

        $mail->line("**Time:** " . now()->toDateTimeString())
             ->action('View Failed Jobs', url('/admin/dashboard'));

        return $mail;
    }

    /**
     * Data stored in the notifications table.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'job_name'  => $this->jobName,
            'error'     => $this->errorMessage,
            'context'   => $this->context,
            'message'   => "Job '{$this->jobName}' failed: {$this->errorMessage}",
            'icon'      => 'error',
        ];
    }

    /**
     * Post a job-failure alert to the #errors Slack channel.
     */
    public function toSlack(object $notifiable): SlackMessage
    {
        $contextLines = collect($this->context)
            ->map(fn ($v, $k) => "• *" . ucfirst(str_replace('_', ' ', $k)) . ":* {$v}")
            ->implode("\n");

        return (new SlackMessage)
            ->to(config('services.slack.notifications.errors_channel', '#errors'))
            ->text(":x: Job Failed — {$this->jobName}")
            ->headerBlock(':red_circle: Job Failure Alert')
            ->sectionBlock(fn (SectionBlock $b) =>
                $b->text(implode("\n", [
                    "*Job:* `{$this->jobName}`",
                    "*Error:* {$this->errorMessage}",
                    $contextLines ? "\n*Context:*\n{$contextLines}" : '',
                ]))->markdown()
            )
            ->contextBlock(fn (ContextBlock $b) =>
                $b->text(':clock1: ' . now()->toDateTimeString() . ' | Env: ' . app()->environment())
            );
    }

    /**
     * Payload for BaseNotification fallback.
     */
    public function getPayload(): array
    {
        return [
            'job_name' => $this->jobName,
            'error'    => $this->errorMessage,
            'context'  => $this->context,
        ];
    }
}
