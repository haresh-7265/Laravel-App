<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;

class FailedJobsSummary extends BaseNotification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(
        public readonly array $failedJobs,
        public readonly array $groupedCounts,
        public readonly int $hours = 24,
    ) {
        $this->onQueue('emails');
    }

    /**
     * Only email — this is a daily digest, not an urgent alert.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $totalCount = count($this->failedJobs);

        $mail = (new MailMessage)
            ->subject("📊 Failed Jobs Summary — {$totalCount} failure(s) in the last {$this->hours}h")
            ->greeting("Failed Jobs Report")
            ->line("There were **{$totalCount}** failed job(s) in the last **{$this->hours} hours**.")
            ->line('');

        // Grouped overview
        $mail->line('**Breakdown by Job Type:**');
        foreach ($this->groupedCounts as $jobName => $count) {
            $mail->line("• **{$jobName}:** {$count} failure(s)");
        }

        $mail->line('');

        // Recent failures (max 10)
        $mail->line('**Recent Failures:**');
        foreach (array_slice($this->failedJobs, 0, 10) as $job) {
            $mail->line("─ **{$job['job_name']}** on `{$job['queue']}` at {$job['failed_at']}");
            $mail->line("  _{$job['error']}_");
        }

        if ($totalCount > 10) {
            $mail->line("… and " . ($totalCount - 10) . " more.");
        }

        $mail->action('View Dashboard', url('/admin/dashboard'));

        return $mail;
    }

    /**
     * Data stored in the notifications table.
     *
     * @return array<string, mixed>
     */
    public function toDatabase(object $notifiable): array
    {
        $totalCount = count($this->failedJobs);

        return [
            'total_failures' => $totalCount,
            'grouped_counts' => $this->groupedCounts,
            'hours'          => $this->hours,
            'message'        => "{$totalCount} job(s) failed in the last {$this->hours} hours.",
            'icon'           => 'warning',
        ];
    }

    /**
     * Payload for BaseNotification fallback.
     */
    public function getPayload(): array
    {
        return [
            'total'   => count($this->failedJobs),
            'grouped' => $this->groupedCounts,
        ];
    }
}
