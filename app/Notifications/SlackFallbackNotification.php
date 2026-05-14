<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SlackFallbackNotification extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(public array $details) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Slack Notification Fallback')
            ->line('A Slack notification failed to deliver after multiple attempts.')
            ->line('**Original Notification Type:** '.($this->details['type'] ?? 'N/A'))
            ->line('**Failure Reason:** '.($this->details['reason'] ?? 'N/A'))
            ->line('**Timestamp:** '.($this->details['timestamp'] ?? now()->toDateTimeString()))
            ->line('**Payload Summary:**')
            ->line(json_encode($this->details['payload'] ?? [], JSON_PRETTY_PRINT))
            ->line('Please check the logs for more details.');
    }
}
